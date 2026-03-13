<?php

namespace Tests\Unit\Services;

use App\Country\CountryRegistry;
use App\Services\ChecklistService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ChecklistServiceTest extends TestCase
{
    private ChecklistService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ChecklistService(new CountryRegistry());
    }

    public function test_get_checklist_uses_cache(): void
    {
        $cachedData = [
            'country' => 'USA',
            'summary' => ['total_employees' => 10, 'complete' => 5],
        ];

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($cachedData);

        $result = $this->service->getChecklist('USA');

        $this->assertEquals($cachedData, $result);
    }

    public function test_invalidate_cache_removes_checklist(): void
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with('checklists:Germany');

        $this->service->invalidateCache('Germany');

        $this->assertTrue(true);
    }

    public function test_get_checklist_returns_country_in_result(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Cache::shouldReceive('get')
            ->once()
            ->with('employees:USA:list', [])
            ->andReturn([]);

        $result = $this->service->getChecklist('USA');

        $this->assertEquals('USA', $result['country']);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('employees', $result);
    }

    public function test_get_checklist_calculates_completion_percentage(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        Cache::shouldReceive('get')
            ->once()
            ->with('employees:USA:list', [])
            ->andReturn([
                ['id' => 1, 'name' => 'John', 'last_name' => 'Doe', 'ssn' => '123-45-6789', 'salary' => 50000, 'address' => '123 Main St'],
                ['id' => 2, 'name' => 'Jane', 'last_name' => 'Doe', 'ssn' => '', 'salary' => 0, 'address' => ''],
            ]);

        $result = $this->service->getChecklist('USA');

        $this->assertEquals(2, $result['summary']['total_employees']);
        $this->assertArrayHasKey('completion_percentage', $result['summary']);
    }
}
