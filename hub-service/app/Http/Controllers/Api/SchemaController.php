<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SchemaController extends Controller
{
    private const CACHE_TTL_HOURS = 24;

    /**
     * GET /api/schema/{step_id}
     */
    public function show(Request $request, string $stepId): JsonResponse
    {
        $request->validate([
            'country' => ['required', 'string', 'in:USA,Germany'],
        ]);

        $country = $request->input('country');
        $cacheKey = "schema:{$stepId}:{$country}";

        $schema = Cache::remember($cacheKey, now()->addHours(self::CACHE_TTL_HOURS), function () use ($stepId, $country) {
            return $this->getSchemaForStep($stepId, $country);
        });

        if (empty($schema)) {
            return response()->json([
                'error' => 'Schema not found',
                'message' => "No schema configuration found for step '{$stepId}' in country '{$country}'",
            ], 404);
        }

        return response()->json([
            'data' => $schema,
            'meta' => [
                'step_id' => $stepId,
                'country' => $country,
            ],
        ]);
    }

    private function getSchemaForStep(string $stepId, string $country): array
    {
        return match ($stepId) {
            'dashboard' => $this->getDashboardSchema($country),
            'employees' => $this->getEmployeesSchema($country),
            'documentation' => $this->getDocumentationSchema($country),
            default => [],
        };
    }

    private function getDashboardSchema(string $country): array
    {
        if (strtoupper($country) === 'GERMANY') {
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

    private function getEmployeesSchema(string $country): array
    {
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

    private function getDocumentationSchema(string $country): array
    {
        if (strtoupper($country) !== 'GERMANY') {
            return [];
        }

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
                ],
            ],
        ];
    }
}
