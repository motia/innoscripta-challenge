<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $country = $this->faker->randomElement(['USA', 'Germany']);

        $data = [
            'name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'salary' => $this->faker->numberBetween(40000, 150000),
            'country' => $country,
        ];

        if ($country === 'USA') {
            $data['ssn'] = $this->faker->numerify('###-##-####');
            $data['address'] = $this->faker->address();
        } else {
            $data['goal'] = $this->faker->sentence();
            $data['tax_id'] = 'DE' . $this->faker->numerify('#########');
        }

        return $data;
    }

    public function usa(): static
    {
        return $this->state(fn (array $attributes) => [
            'country' => 'USA',
            'ssn' => $this->faker->numerify('###-##-####'),
            'address' => $this->faker->address(),
            'goal' => null,
            'tax_id' => null,
        ]);
    }

    public function germany(): static
    {
        return $this->state(fn (array $attributes) => [
            'country' => 'Germany',
            'ssn' => null,
            'address' => null,
            'goal' => $this->faker->sentence(),
            'tax_id' => 'DE' . $this->faker->numerify('#########'),
        ]);
    }
}
