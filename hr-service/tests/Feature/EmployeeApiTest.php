<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_employees(): void
    {
        Employee::factory()->usa()->count(3)->create();
        Employee::factory()->germany()->count(2)->create();

        $response = $this->getJson('/api/employees');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'last_name', 'salary', 'country'],
                ],
                'links',
                'meta',
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    public function test_can_filter_employees_by_country(): void
    {
        Employee::factory()->usa()->count(3)->create();
        Employee::factory()->germany()->count(2)->create();

        $response = $this->getJson('/api/employees?country=USA');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));

        foreach ($response->json('data') as $employee) {
            $this->assertEquals('USA', $employee['country']);
        }
    }

    public function test_can_create_usa_employee(): void
    {
        $data = [
            'name' => 'John',
            'last_name' => 'Doe',
            'salary' => 75000,
            'country' => 'USA',
            'ssn' => '123-45-6789',
            'address' => '123 Main Street, New York, NY',
        ];

        $response = $this->postJson('/api/employees', $data);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'John',
                'country' => 'USA',
                'ssn' => '123-45-6789',
            ]);

        $this->assertDatabaseHas('employees', [
            'name' => 'John',
            'country' => 'USA',
            'ssn' => '123-45-6789',
        ]);
    }

    public function test_can_create_germany_employee(): void
    {
        $data = [
            'name' => 'Hans',
            'last_name' => 'Mueller',
            'salary' => 65000,
            'country' => 'Germany',
            'goal' => 'Become team lead',
            'tax_id' => 'DE123456789',
        ];

        $response = $this->postJson('/api/employees', $data);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Hans',
                'country' => 'Germany',
                'goal' => 'Become team lead',
                'tax_id' => 'DE123456789',
            ]);

        $this->assertDatabaseHas('employees', [
            'name' => 'Hans',
            'country' => 'Germany',
            'tax_id' => 'DE123456789',
        ]);
    }

    public function test_create_employee_requires_country(): void
    {
        $data = [
            'name' => 'John',
            'last_name' => 'Doe',
            'salary' => 75000,
        ];

        $response = $this->postJson('/api/employees', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country']);
    }

    public function test_create_usa_employee_requires_ssn(): void
    {
        $data = [
            'name' => 'John',
            'last_name' => 'Doe',
            'salary' => 75000,
            'country' => 'USA',
            'address' => '123 Main St',
        ];

        $response = $this->postJson('/api/employees', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ssn']);
    }

    public function test_create_germany_employee_requires_tax_id(): void
    {
        $data = [
            'name' => 'Hans',
            'last_name' => 'Mueller',
            'salary' => 65000,
            'country' => 'Germany',
            'goal' => 'Become manager',
        ];

        $response = $this->postJson('/api/employees', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tax_id']);
    }

    public function test_usa_ssn_must_match_format(): void
    {
        $data = [
            'name' => 'John',
            'last_name' => 'Doe',
            'salary' => 75000,
            'country' => 'USA',
            'ssn' => 'invalid-ssn',
            'address' => '123 Main St',
        ];

        $response = $this->postJson('/api/employees', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ssn']);
    }

    public function test_germany_tax_id_must_match_format(): void
    {
        $data = [
            'name' => 'Hans',
            'last_name' => 'Mueller',
            'salary' => 65000,
            'country' => 'Germany',
            'goal' => 'Become manager',
            'tax_id' => 'invalid-tax-id',
        ];

        $response = $this->postJson('/api/employees', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tax_id']);
    }

    public function test_can_show_employee(): void
    {
        $employee = Employee::factory()->usa()->create();

        $response = $this->getJson("/api/employees/{$employee->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $employee->id,
                'name' => $employee->name,
            ]);
    }

    public function test_can_update_employee(): void
    {
        $employee = Employee::factory()->usa()->create();

        $response = $this->putJson("/api/employees/{$employee->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Name']);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_employee(): void
    {
        $employee = Employee::factory()->usa()->create();

        $response = $this->deleteJson("/api/employees/{$employee->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_usa_employee_response_includes_custom_fields(): void
    {
        $employee = Employee::factory()->usa()->create([
            'ssn' => '111-22-3333',
            'address' => '456 Oak Ave',
        ]);

        $response = $this->getJson("/api/employees/{$employee->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'ssn' => '111-22-3333',
                'address' => '456 Oak Ave',
            ])
            ->assertJsonMissing(['goal', 'tax_id']);
    }

    public function test_germany_employee_response_includes_custom_fields(): void
    {
        $employee = Employee::factory()->germany()->create([
            'goal' => 'Lead projects',
            'tax_id' => 'DE987654321',
        ]);

        $response = $this->getJson("/api/employees/{$employee->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'goal' => 'Lead projects',
                'tax_id' => 'DE987654321',
            ])
            ->assertJsonMissing(['ssn', 'address']);
    }

    public function test_unsupported_country_fails_validation(): void
    {
        $data = [
            'name' => 'Pierre',
            'last_name' => 'Dupont',
            'salary' => 55000,
            'country' => 'France',
        ];

        $response = $this->postJson('/api/employees', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country']);
    }
}
