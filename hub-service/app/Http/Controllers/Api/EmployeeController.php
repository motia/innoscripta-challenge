<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EmployeeController extends Controller
{
    private const CACHE_TTL_HOURS = 1;

    /**
     * GET /api/employees
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'country' => ['required', 'string', 'in:USA,Germany'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $country = $request->input('country');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 15);

        $cacheKey = "employees:{$country}:list";
        $employees = Cache::get($cacheKey, []);

        $total = count($employees);
        $offset = ($page - 1) * $perPage;
        $paginatedEmployees = array_slice($employees, $offset, $perPage);

        $columns = $this->getColumnsForCountry($country);

        return response()->json([
            'data' => $paginatedEmployees,
            'columns' => $columns,
            'meta' => [
                'country' => $country,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    private function getColumnsForCountry(string $country): array
    {
        $baseColumns = [
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
            ['key' => 'last_name', 'label' => 'Last Name', 'sortable' => true],
            ['key' => 'salary', 'label' => 'Salary', 'sortable' => true, 'format' => 'currency'],
        ];

        if (strtoupper($country) === 'USA') {
            $baseColumns[] = ['key' => 'ssn', 'label' => 'SSN', 'sortable' => false, 'masked' => true];
        } elseif (strtoupper($country) === 'GERMANY') {
            $baseColumns[] = ['key' => 'goal', 'label' => 'Goal', 'sortable' => false];
        }

        return $baseColumns;
    }
}
