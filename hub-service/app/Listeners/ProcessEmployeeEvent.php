<?php

namespace App\Listeners;

use App\Events\ChecklistUpdated;
use App\Events\EmployeeDeleted;
use App\Events\EmployeeUpdated;
use App\Services\ChecklistService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessEmployeeEvent
{
    private const EMPLOYEE_CACHE_TTL_HOURS = 1;

    public function __construct(
        private readonly ChecklistService $checklistService
    ) {}

    /**
     * Handle employee created event.
     */
    public function handleCreated(array $payload): void
    {
        $this->processEvent('created', $payload);
    }

    /**
     * Handle employee updated event.
     */
    public function handleUpdated(array $payload): void
    {
        $this->processEvent('updated', $payload);
    }

    /**
     * Handle employee deleted event.
     */
    public function handleDeleted(array $payload): void
    {
        $this->processEvent('deleted', $payload);
    }

    private function processEvent(string $eventType, array $payload): void
    {
        try {
            $country = $payload['country'] ?? null;
            $employee = $payload['data']['employee'] ?? null;
            $employeeId = $payload['data']['employee_id'] ?? $employee['id'] ?? null;

            if (!$country) {
                Log::warning('Employee event missing country', ['payload' => $payload]);
                return;
            }

            Log::info("Processing employee {$eventType} event", [
                'event_id' => $payload['event_id'] ?? null,
                'country' => $country,
                'employee_id' => $employeeId,
            ]);

            match ($eventType) {
                'created', 'updated' => $this->updateEmployeeCache($country, $employee),
                'deleted' => $this->removeEmployeeFromCache($country, $employeeId),
            };

            $this->invalidateRelatedCaches($country, $employeeId);

            $this->broadcastUpdates($eventType, $country, $employee, $employeeId, $payload);

            Log::info("Successfully processed employee {$eventType} event", [
                'employee_id' => $employeeId,
                'country' => $country,
            ]);

        } catch (\Throwable $e) {
            Log::error("Failed to process employee {$eventType} event", [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            throw $e;
        }
    }

    private function updateEmployeeCache(string $country, array $employee): void
    {
        $employeeId = $employee['id'];

        Cache::put(
            "employees:{$country}:{$employeeId}",
            $employee,
            now()->addHours(self::EMPLOYEE_CACHE_TTL_HOURS)
        );

        $listKey = "employees:{$country}:list";
        $employees = Cache::get($listKey, []);

        $found = false;
        foreach ($employees as $index => $existingEmployee) {
            if (($existingEmployee['id'] ?? null) === $employeeId) {
                $employees[$index] = $employee;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $employees[] = $employee;
        }

        Cache::put($listKey, $employees, now()->addHours(self::EMPLOYEE_CACHE_TTL_HOURS));
    }

    private function removeEmployeeFromCache(string $country, int $employeeId): void
    {
        Cache::forget("employees:{$country}:{$employeeId}");

        $listKey = "employees:{$country}:list";
        $employees = Cache::get($listKey, []);

        $employees = array_filter($employees, fn($e) => ($e['id'] ?? null) !== $employeeId);
        $employees = array_values($employees);

        Cache::put($listKey, $employees, now()->addHours(self::EMPLOYEE_CACHE_TTL_HOURS));
    }

    private function invalidateRelatedCaches(string $country, ?int $employeeId): void
    {
        $this->checklistService->invalidateCache($country);
    }

    private function broadcastUpdates(
        string $eventType,
        string $country,
        ?array $employee,
        ?int $employeeId,
        array $payload
    ): void {
        if ($eventType === 'deleted') {
            event(new EmployeeDeleted($country, $employeeId));
        } else {
            $changedFields = $payload['data']['changed_fields'] ?? [];
            event(new EmployeeUpdated($country, $employee, $changedFields));
        }

        $checklist = $this->checklistService->getChecklist($country);
        event(new ChecklistUpdated($country, $checklist['summary'] ?? []));
    }
}
