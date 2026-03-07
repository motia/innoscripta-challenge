<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HubApiTest extends TestCase
{
    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/up');

        $response->assertStatus(200);
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

        $response = $this->getJson('/api/checklist/USA');

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
        $response = $this->getJson('/api/steps/USA');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'country',
                'steps' => [
                    '*' => ['id', 'title', 'description'],
                ],
            ]);
    }

    public function test_steps_endpoint_returns_different_steps_for_germany(): void
    {
        $response = $this->getJson('/api/steps/Germany');

        $response->assertStatus(200)
            ->assertJsonFragment(['country' => 'Germany']);
    }

    public function test_employees_endpoint_returns_paginated_list(): void
    {
        Cache::shouldReceive('get')
            ->once()
            ->with('employees:USA:list', [])
            ->andReturn([
                ['id' => 1, 'name' => 'John', 'last_name' => 'Doe', 'country' => 'USA'],
                ['id' => 2, 'name' => 'Jane', 'last_name' => 'Smith', 'country' => 'USA'],
            ]);

        $response = $this->getJson('/api/employees/USA');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'country',
                'columns',
                'employees',
            ]);
    }

    public function test_schema_endpoint_returns_widget_config(): void
    {
        $response = $this->getJson('/api/schema/personal_info?country=USA');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'step_id',
                'country',
                'widgets',
            ]);
    }

    public function test_unsupported_country_returns_error(): void
    {
        $response = $this->getJson('/api/steps/France');

        $response->assertStatus(400)
            ->assertJsonFragment(['error' => 'Unsupported country: France']);
    }
}
