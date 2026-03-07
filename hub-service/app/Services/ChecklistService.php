<?php

namespace App\Services;

use App\Validation\CountryValidationFactory;
use Illuminate\Support\Facades\Cache;
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
}
