<?php

namespace App\Services;

use App\Validation\CountryValidationFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ChecklistService
{
    private const CACHE_TTL_MINUTES = 30;

    /**
     * Get checklist data for a country.
     */
    public function getChecklist(string $country): array
    {
        $cacheKey = "checklists:{$country}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($country) {
            return $this->calculateChecklist($country);
        });
    }

    /**
     * Calculate checklist data for all employees in a country.
     */
    private function calculateChecklist(string $country): array
    {
        $employees = $this->getEmployeesFromCache($country);
        $strategy = CountryValidationFactory::make($country);
        $checklistRules = $strategy->checklistRules();

        $employeeChecklists = [];
        $totalComplete = 0;
        $totalEmployees = count($employees);

        foreach ($employees as $employee) {
            $employeeChecklist = $this->validateEmployee($employee, $checklistRules);
            $employeeChecklists[] = $employeeChecklist;

            if ($employeeChecklist['is_complete']) {
                $totalComplete++;
            }
        }

        return [
            'country' => $country,
            'summary' => [
                'total_employees' => $totalEmployees,
                'complete' => $totalComplete,
                'incomplete' => $totalEmployees - $totalComplete,
                'completion_percentage' => $totalEmployees > 0
                    ? round(($totalComplete / $totalEmployees) * 100, 2)
                    : 0,
            ],
            'employees' => $employeeChecklists,
        ];
    }

    /**
     * Validate a single employee against checklist rules.
     */
    private function validateEmployee(array $employee, array $checklistRules): array
    {
        $completedFields = [];
        $missingFields = [];

        foreach ($checklistRules as $key => $ruleConfig) {
            $field = $ruleConfig['field'];
            $value = $employee[$field] ?? null;

            $validator = Validator::make(
                [$field => $value],
                [$field => $ruleConfig['rule']]
            );

            if ($validator->passes()) {
                $completedFields[] = [
                    'field' => $field,
                    'label' => $ruleConfig['label'],
                ];
            } else {
                $missingFields[] = [
                    'field' => $field,
                    'label' => $ruleConfig['label'],
                    'message' => $ruleConfig['message'],
                ];
            }
        }

        $totalFields = count($checklistRules);
        $completedCount = count($completedFields);

        return [
            'employee_id' => $employee['id'] ?? null,
            'employee_name' => ($employee['name'] ?? '') . ' ' . ($employee['last_name'] ?? ''),
            'is_complete' => empty($missingFields),
            'completion_percentage' => $totalFields > 0
                ? round(($completedCount / $totalFields) * 100, 2)
                : 0,
            'completed_fields' => $completedFields,
            'missing_fields' => $missingFields,
        ];
    }

    /**
     * Get employees from cache for a country.
     */
    private function getEmployeesFromCache(string $country): array
    {
        $cacheKey = "employees:{$country}:list";

        return Cache::get($cacheKey, []);
    }

    /**
     * Invalidate checklist cache for a country.
     */
    public function invalidateCache(string $country): void
    {
        Cache::forget("checklists:{$country}");
    }

    /**
     * Apply delta update for employee created/updated.
     * Uses Redis lock to prevent race conditions.
     */
    public function applyEmployeeDelta(string $country, array $employee, ?array $previousEmployee = null): void
    {
        $lockKey = "checklists:{$country}:lock";
        $cacheKey = "checklists:{$country}";

        $lock = Cache::lock($lockKey, 10);

        try {
            $lock->block(5);

            $checklist = Cache::get($cacheKey);

            if (!$checklist) {
                $checklist = $this->calculateChecklist($country);
                Cache::put($cacheKey, $checklist, now()->addMinutes(self::CACHE_TTL_MINUTES));
                return;
            }

            $strategy = CountryValidationFactory::make($country);
            $checklistRules = $strategy->checklistRules();
            $newEmployeeChecklist = $this->validateEmployee($employee, $checklistRules);

            $wasComplete = false;
            $existingIndex = null;

            foreach ($checklist['employees'] as $index => $empChecklist) {
                if ($empChecklist['employee_id'] === $employee['id']) {
                    $wasComplete = $empChecklist['is_complete'];
                    $existingIndex = $index;
                    break;
                }
            }

            if ($existingIndex !== null) {
                $checklist['employees'][$existingIndex] = $newEmployeeChecklist;
            } else {
                $checklist['employees'][] = $newEmployeeChecklist;
                $checklist['summary']['total_employees']++;
            }

            if ($existingIndex !== null) {
                if ($wasComplete && !$newEmployeeChecklist['is_complete']) {
                    $checklist['summary']['complete']--;
                    $checklist['summary']['incomplete']++;
                } elseif (!$wasComplete && $newEmployeeChecklist['is_complete']) {
                    $checklist['summary']['complete']++;
                    $checklist['summary']['incomplete']--;
                }
            } else {
                if ($newEmployeeChecklist['is_complete']) {
                    $checklist['summary']['complete']++;
                } else {
                    $checklist['summary']['incomplete']++;
                }
            }

            $checklist['summary']['completion_percentage'] = $checklist['summary']['total_employees'] > 0
                ? round(($checklist['summary']['complete'] / $checklist['summary']['total_employees']) * 100, 2)
                : 0;

            Cache::put($cacheKey, $checklist, now()->addMinutes(self::CACHE_TTL_MINUTES));

            Log::debug('Applied employee delta to checklist cache', [
                'country' => $country,
                'employee_id' => $employee['id'],
                'was_update' => $existingIndex !== null,
            ]);

        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            Log::warning('Failed to acquire checklist lock, falling back to invalidation', [
                'country' => $country,
            ]);
            $this->invalidateCache($country);
        } finally {
            $lock?->release();
        }
    }

    /**
     * Apply delta update for employee deleted.
     * Uses Redis lock to prevent race conditions.
     */
    public function applyEmployeeDeleteDelta(string $country, int $employeeId): void
    {
        $lockKey = "checklists:{$country}:lock";
        $cacheKey = "checklists:{$country}";

        $lock = Cache::lock($lockKey, 10);

        try {
            $lock->block(5);

            $checklist = Cache::get($cacheKey);

            if (!$checklist) {
                return;
            }

            $wasComplete = false;
            $foundIndex = null;

            foreach ($checklist['employees'] as $index => $empChecklist) {
                if ($empChecklist['employee_id'] === $employeeId) {
                    $wasComplete = $empChecklist['is_complete'];
                    $foundIndex = $index;
                    break;
                }
            }

            if ($foundIndex === null) {
                return;
            }

            array_splice($checklist['employees'], $foundIndex, 1);

            $checklist['summary']['total_employees']--;
            if ($wasComplete) {
                $checklist['summary']['complete']--;
            } else {
                $checklist['summary']['incomplete']--;
            }

            $checklist['summary']['completion_percentage'] = $checklist['summary']['total_employees'] > 0
                ? round(($checklist['summary']['complete'] / $checklist['summary']['total_employees']) * 100, 2)
                : 0;

            Cache::put($cacheKey, $checklist, now()->addMinutes(self::CACHE_TTL_MINUTES));

            Log::debug('Applied employee delete delta to checklist cache', [
                'country' => $country,
                'employee_id' => $employeeId,
            ]);

        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            Log::warning('Failed to acquire checklist lock for delete, falling back to invalidation', [
                'country' => $country,
            ]);
            $this->invalidateCache($country);
        } finally {
            $lock?->release();
        }
    }
}
