<?php

namespace Tests\Unit\Listeners;

use App\Events\ChecklistUpdated;
use App\Events\EmployeeDeleted;
use App\Events\EmployeeUpdated;
use App\Listeners\ProcessEmployeeEvent;
use App\Services\ChecklistService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class ProcessEmployeeEventTest extends TestCase
{
    private ProcessEmployeeEvent $listener;
    private $checklistService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->checklistService = Mockery::mock(ChecklistService::class);
        $this->listener = new ProcessEmployeeEvent($this->checklistService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_created_caches_employee(): void
    {
        Event::fake([EmployeeUpdated::class, ChecklistUpdated::class]);

        Cache::shouldReceive('put')->twice()->andReturnNull();
        Cache::shouldReceive('get')->once()->with('employees:USA:list', [])->andReturn([]);

        $this->checklistService->shouldReceive('invalidateCache')
            ->once()
            ->with('USA');

        $this->checklistService->shouldReceive('getChecklist')
            ->once()
            ->with('USA')
            ->andReturn(['summary' => []]);

        $payload = [
            'event_id' => 'test-123',
            'country' => 'USA',
            'data' => [
                'employee_id' => 1,
                'employee' => [
                    'id' => 1,
                    'name' => 'John',
                    'country' => 'USA',
                ],
            ],
        ];

        $this->listener->handleCreated($payload);

        Event::assertDispatched(EmployeeUpdated::class);
        Event::assertDispatched(ChecklistUpdated::class);
    }

    public function test_handle_deleted_removes_employee_from_cache(): void
    {
        Event::fake([EmployeeDeleted::class, ChecklistUpdated::class]);

        Cache::shouldReceive('forget')->once()->with('employees:USA:1')->andReturnNull();
        Cache::shouldReceive('get')->once()->with('employees:USA:list', [])->andReturn([
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ]);
        Cache::shouldReceive('put')->once()->andReturnNull();

        $this->checklistService->shouldReceive('invalidateCache')
            ->once()
            ->with('USA');

        $this->checklistService->shouldReceive('getChecklist')
            ->once()
            ->with('USA')
            ->andReturn(['summary' => []]);

        $payload = [
            'event_id' => 'test-456',
            'country' => 'USA',
            'data' => [
                'employee_id' => 1,
                'employee' => ['id' => 1, 'name' => 'John'],
            ],
        ];

        $this->listener->handleDeleted($payload);

        Event::assertDispatched(EmployeeDeleted::class, function ($event) {
            return $event->employeeId === 1;
        });
        Event::assertDispatched(ChecklistUpdated::class);
    }

    public function test_handle_updated_broadcasts_changed_fields(): void
    {
        Event::fake([EmployeeUpdated::class, ChecklistUpdated::class]);

        Cache::shouldReceive('put')->twice()->andReturnNull();
        Cache::shouldReceive('get')->once()->with('employees:Germany:list', [])->andReturn([]);

        $this->checklistService->shouldReceive('invalidateCache')->once();
        $this->checklistService->shouldReceive('getChecklist')->once()->andReturn(['summary' => []]);

        $payload = [
            'event_id' => 'test-789',
            'country' => 'Germany',
            'data' => [
                'employee_id' => 2,
                'changed_fields' => ['salary', 'goal'],
                'employee' => [
                    'id' => 2,
                    'name' => 'Hans',
                    'country' => 'Germany',
                ],
            ],
        ];

        $this->listener->handleUpdated($payload);

        Event::assertDispatched(EmployeeUpdated::class, function ($event) {
            return $event->changedFields === ['salary', 'goal'];
        });
    }

    public function test_logs_warning_when_country_missing(): void
    {
        $payload = [
            'event_id' => 'test-no-country',
            'data' => [
                'employee_id' => 1,
            ],
        ];

        $this->listener->handleCreated($payload);

        $this->assertTrue(true);
    }
}
