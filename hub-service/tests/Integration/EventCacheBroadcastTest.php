<?php

namespace Tests\Integration;

use App\Events\ChecklistUpdated;
use App\Events\EmployeeDeleted;
use App\Events\EmployeeUpdated;
use App\Listeners\ProcessEmployeeEvent;
use App\Services\ChecklistService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class EventCacheBroadcastTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_employee_created_event_updates_cache_and_broadcasts(): void
    {
        $checklistService = Mockery::mock(ChecklistService::class);
        $checklistService->shouldReceive('invalidateCache')->once()->with('USA');
        $checklistService->shouldReceive('getChecklist')->once()->with('USA')->andReturn([
            'summary' => ['total_employees' => 1, 'complete' => 1],
        ]);

        $listener = new ProcessEmployeeEvent($checklistService);

        Cache::shouldReceive('put')->twice();
        Cache::shouldReceive('get')->once()->with('employees:USA:list', [])->andReturn([]);

        Event::fake([EmployeeUpdated::class, ChecklistUpdated::class]);

        $payload = [
            'event_type' => 'EmployeeCreated',
            'event_id' => 'integration-test-1',
            'timestamp' => now()->toIso8601String(),
            'country' => 'USA',
            'data' => [
                'employee_id' => 100,
                'employee' => [
                    'id' => 100,
                    'name' => 'Integration',
                    'last_name' => 'Test',
                    'country' => 'USA',
                    'ssn' => '999-88-7777',
                    'salary' => 100000,
                    'address' => '1 Test Lane',
                ],
            ],
        ];

        $listener->handleCreated($payload);

        Event::assertDispatched(EmployeeUpdated::class, function ($event) {
            return $event->country === 'USA' &&
                   $event->employee['id'] === 100;
        });

        Event::assertDispatched(ChecklistUpdated::class, function ($event) {
            return $event->country === 'USA';
        });
    }

    public function test_employee_updated_event_refreshes_cache_and_broadcasts_changes(): void
    {
        $checklistService = Mockery::mock(ChecklistService::class);
        $checklistService->shouldReceive('invalidateCache')->once()->with('Germany');
        $checklistService->shouldReceive('getChecklist')->once()->with('Germany')->andReturn([
            'summary' => ['total_employees' => 1, 'complete' => 1],
        ]);

        $listener = new ProcessEmployeeEvent($checklistService);

        Cache::shouldReceive('put')->twice();
        Cache::shouldReceive('get')->once()->with('employees:Germany:list', [])->andReturn([
            ['id' => 200, 'name' => 'Old', 'last_name' => 'Name'],
        ]);

        Event::fake([EmployeeUpdated::class, ChecklistUpdated::class]);

        $payload = [
            'event_type' => 'EmployeeUpdated',
            'event_id' => 'integration-test-2',
            'timestamp' => now()->toIso8601String(),
            'country' => 'Germany',
            'data' => [
                'employee_id' => 200,
                'changed_fields' => ['salary', 'goal'],
                'employee' => [
                    'id' => 200,
                    'name' => 'Updated',
                    'last_name' => 'Employee',
                    'country' => 'Germany',
                    'goal' => 'New goal',
                    'salary' => 75000,
                    'tax_id' => 'DE123456789',
                ],
            ],
        ];

        $listener->handleUpdated($payload);

        Event::assertDispatched(EmployeeUpdated::class, function ($event) {
            return $event->country === 'Germany' &&
                   $event->changedFields === ['salary', 'goal'];
        });

        Event::assertDispatched(ChecklistUpdated::class);
    }

    public function test_employee_deleted_event_removes_from_cache_and_broadcasts(): void
    {
        $checklistService = Mockery::mock(ChecklistService::class);
        $checklistService->shouldReceive('invalidateCache')->once()->with('USA');
        $checklistService->shouldReceive('getChecklist')->once()->with('USA')->andReturn([
            'summary' => ['total_employees' => 0, 'complete' => 0],
        ]);

        $listener = new ProcessEmployeeEvent($checklistService);

        Cache::shouldReceive('forget')->once()->with('employees:USA:300');
        Cache::shouldReceive('get')->once()->with('employees:USA:list', [])->andReturn([
            ['id' => 300, 'name' => 'To Delete'],
            ['id' => 301, 'name' => 'Keep This'],
        ]);
        Cache::shouldReceive('put')->once();

        Event::fake([EmployeeDeleted::class, ChecklistUpdated::class]);

        $payload = [
            'event_type' => 'EmployeeDeleted',
            'event_id' => 'integration-test-3',
            'timestamp' => now()->toIso8601String(),
            'country' => 'USA',
            'data' => [
                'employee_id' => 300,
                'employee' => [
                    'id' => 300,
                    'name' => 'To Delete',
                    'country' => 'USA',
                ],
            ],
        ];

        $listener->handleDeleted($payload);

        Event::assertDispatched(EmployeeDeleted::class, function ($event) {
            return $event->employeeId === 300 &&
                   $event->country === 'USA';
        });

        Event::assertDispatched(ChecklistUpdated::class);
    }

    public function test_full_flow_create_update_delete(): void
    {
        $checklistService = Mockery::mock(ChecklistService::class);
        $checklistService->shouldReceive('invalidateCache')->times(3);
        $checklistService->shouldReceive('getChecklist')->times(3)->andReturn(['summary' => []]);

        $listener = new ProcessEmployeeEvent($checklistService);

        Cache::shouldReceive('put')->times(5);
        Cache::shouldReceive('get')->times(3)->andReturn([]);
        Cache::shouldReceive('forget')->once();

        Event::fake();

        $listener->handleCreated([
            'event_id' => 'flow-1',
            'country' => 'USA',
            'data' => [
                'employee_id' => 1,
                'employee' => ['id' => 1, 'name' => 'Test', 'country' => 'USA'],
            ],
        ]);

        $listener->handleUpdated([
            'event_id' => 'flow-2',
            'country' => 'USA',
            'data' => [
                'employee_id' => 1,
                'changed_fields' => ['name'],
                'employee' => ['id' => 1, 'name' => 'Updated', 'country' => 'USA'],
            ],
        ]);

        $listener->handleDeleted([
            'event_id' => 'flow-3',
            'country' => 'USA',
            'data' => [
                'employee_id' => 1,
                'employee' => ['id' => 1, 'name' => 'Updated', 'country' => 'USA'],
            ],
        ]);

        Event::assertDispatched(EmployeeUpdated::class, 2);
        Event::assertDispatched(EmployeeDeleted::class, 1);
        Event::assertDispatched(ChecklistUpdated::class, 3);
    }
}
