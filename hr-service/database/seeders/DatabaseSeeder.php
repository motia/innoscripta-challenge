<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create USA employees
        Employee::factory()->usa()->count(5)->create();

        // Create Germany employees
        Employee::factory()->germany()->count(5)->create();
    }
}
