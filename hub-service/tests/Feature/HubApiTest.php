<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HubApiTest extends TestCase
{
    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'ok']);
    }

    public function test_checklist_endpoint_returns_data(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn([
                'country' => 'USA',
                'summary' => [
                    'total_employees' => 5,
                    'complete' => 3,
                    'incomplete' => 2,
                    'completion_percentage' => 60.0,
                ],
                'employees' => [],
            ]);

        $response = $this->getJson('/api/checklists?country=USA');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'country',
                'summary' => [
                    'total_employees',
                    'complete',
                    'incomplete',
                    'completion_percentage',
                ],
            ]);
    }

    public function test_steps_endpoint_returns_country_steps(): void
    {
        Cache::shouldReceive('remember')->once()->andReturn([
            ['id' => 'dashboard', 'label' => 'Dashboard'],
        ]);

        $response = $this->getJson('/api/steps?country=USA');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['country'],
            ]);
    }

    public function test_steps_endpoint_returns_different_steps_for_germany(): void
    {
        Cache::shouldReceive('remember')->once()->andReturn([
            ['id' => 'dashboard', 'label' => 'Dashboard'],
            ['id' => 'documentation', 'label' => 'Documentation'],
        ]);

        $response = $this->getJson('/api/steps?country=Germany');

        $response->assertStatus(200)
            ->assertJsonFragment(['country' => 'Germany']);
    }

    public function test_employees_endpoint_returns_list(): void
    {
        Cache::shouldReceive('get')
            ->once()
            ->with('employees:USA:list', [])
            ->andReturn([
                ['id' => 1, 'name' => 'John', 'last_name' => 'Doe', 'country' => 'USA'],
                ['id' => 2, 'name' => 'Jane', 'last_name' => 'Smith', 'country' => 'USA'],
            ]);

        $response = $this->getJson('/api/employees?country=USA');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'columns',
                'meta' => ['country'],
            ]);
    }

    public function test_schema_endpoint_returns_widget_config(): void
    {
        Cache::shouldReceive('remember')->once()->andReturn([
            'layout' => 'grid',
            'widgets' => [],
        ]);

        $response = $this->getJson('/api/schema/dashboard?country=USA');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['step_id', 'country'],
            ]);
    }

    public function test_unsupported_country_returns_validation_error(): void
    {
        $response = $this->getJson('/api/steps?country=France');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country']);
    }
}
