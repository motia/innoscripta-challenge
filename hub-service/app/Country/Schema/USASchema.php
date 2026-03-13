<?php

namespace App\Country\Schema;

class USASchema implements CountrySchema
{
    public function getCountryCode(): string
    {
        return 'USA';
    }

    public function getStepSchema(string $stepId): array
    {
        return match ($stepId) {
            'dashboard' => $this->getDashboardSchema(),
            'employees' => $this->getEmployeesSchema(),
            default => [],
        };
    }

    public function getDashboardSchema(): array
    {
        $country = $this->getCountryCode();

        return [
            'layout' => 'grid',
            'columns' => 3,
            'widgets' => [
                [
                    'id' => 'employee_count',
                    'type' => 'stat_card',
                    'label' => 'Total Employees',
                    'data_source' => '/api/employees',
                    'data_key' => 'meta.total',
                    'icon' => 'users',
                    'channel' => "country.{$country}",
                ],
                [
                    'id' => 'average_salary',
                    'type' => 'stat_card',
                    'label' => 'Average Salary',
                    'data_source' => '/api/employees',
                    'data_key' => 'computed.average_salary',
                    'icon' => 'dollar-sign',
                    'format' => 'currency',
                    'channel' => "country.{$country}",
                ],
                [
                    'id' => 'completion_rate',
                    'type' => 'progress_card',
                    'label' => 'Data Completion Rate',
                    'data_source' => '/api/checklists',
                    'data_key' => 'summary.completion_percentage',
                    'icon' => 'check-circle',
                    'format' => 'percentage',
                    'channel' => "country.{$country}.checklists",
                ],
            ],
        ];
    }

    public function getEmployeesSchema(): array
    {
        $country = $this->getCountryCode();

        return [
            'layout' => 'table',
            'data_source' => '/api/employees',
            'channel' => "country.{$country}",
            'features' => [
                'pagination' => true,
                'sorting' => true,
                'filtering' => false,
            ],
        ];
    }


    public function getSteps(): array
    {
        return [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'home',
                'route' => '/dashboard',
            ],
            [
                'id' => 'employees',
                'label' => 'Employees',
                'icon' => 'users',
                'route' => '/employees',
            ],
        ];
    }
}
