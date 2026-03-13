<?php

namespace App\Country\Schema;

class GermanySchema implements CountrySchema
{
    public function getCountryCode(): string
    {
        return 'Germany';
    }

    public function getStepSchema(string $stepId): array
    {
        return match ($stepId) {
            'dashboard' => $this->getDashboardSchema(),
            'employees' => $this->getEmployeesSchema(),
            'documentation' => $this->getDocumentationSchema(),
            default => [],
        };
    }

    public function getDashboardSchema(): array
    {
        $country = $this->getCountryCode();

        return [
            'layout' => 'grid',
            'columns' => 2,
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
                    'id' => 'goal_tracking',
                    'type' => 'list_card',
                    'label' => 'Goal Tracking',
                    'data_source' => '/api/employees',
                    'data_key' => 'data',
                    'display_fields' => ['name', 'goal'],
                    'icon' => 'target',
                    'channel' => "country.{$country}",
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

    public function getDocumentationSchema(): array
    {
        return [
            'layout' => 'document_list',
            'widgets' => [
                [
                    'id' => 'required_documents',
                    'type' => 'checklist',
                    'label' => 'Required Documents',
                    'items' => [
                        ['id' => 'tax_certificate', 'label' => 'Tax Certificate'],
                        ['id' => 'work_permit', 'label' => 'Work Permit'],
                        ['id' => 'health_insurance', 'label' => 'Health Insurance'],
                    ],
                ],m
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
            [
                'id' => 'documentation',
                'label' => 'Documentation',
                'icon' => 'file-text',
                'route' => '/documentation',
            ],
        ];
    }
}
