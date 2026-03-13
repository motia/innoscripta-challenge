<?php

namespace App\Http\Controllers\Api;

use App\Country\CountryRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EmployeeController extends Controller
{
    private const CACHE_TTL_HOURS = 1;

    public function __construct(
        private readonly CountryRegistry $registry
    ) {}

    /**
     * GET /api/employees
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'country' => ['required', 'string', 'in:' . $this->registry->supportedCountriesString()],
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

        $strategy = $this->registry->getValidation($country);
        $columns = $this->buildColumnDefinitions($strategy->listColumns());

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

    private function buildColumnDefinitions(array $columnKeys): array
    {
        $definitions = [
            'id'        => ['key' => 'id',        'label' => 'ID',        'sortable' => true],
            'name'      => ['key' => 'name',      'label' => 'Name',      'sortable' => true],
            'last_name' => ['key' => 'last_name', 'label' => 'Last Name', 'sortable' => true],
            'salary'    => ['key' => 'salary',    'label' => 'Salary',    'sortable' => true,  'format' => 'currency'],
            'country'   => ['key' => 'country',   'label' => 'Country',   'sortable' => true],
            'ssn'       => ['key' => 'ssn',       'label' => 'SSN',       'sortable' => false, 'masked' => true],
            'address'   => ['key' => 'address',   'label' => 'Address',   'sortable' => false],
            'goal'      => ['key' => 'goal',      'label' => 'Goal',      'sortable' => false],
            'tax_id'    => ['key' => 'tax_id',    'label' => 'Tax ID',    'sortable' => false],
        ];

        return array_values(array_filter(
            array_map(fn($key) => $definitions[$key] ?? null, $columnKeys)
        ));
    }
}
