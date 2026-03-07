<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Services\EmployeeEventPublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeEventPublisher $eventPublisher
    ) {}

    /**
     * GET /api/employees
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Employee::query();

        if ($request->has('country')) {
            $query->byCountry($request->input('country'));
        }

        $employees = $query->paginate($request->input('per_page', 15));

        return EmployeeResource::collection($employees);
    }

    /**
     * POST /api/employees
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = Employee::create($request->validated());

        $this->eventPublisher->publishCreated($employee, $employee->created_at);

        return response()->json(
            new EmployeeResource($employee),
            201
        );
    }

    /**
     * GET /api/employees/{employee}
     */
    public function show(Employee $employee): EmployeeResource
    {
        return new EmployeeResource($employee);
    }

    /**
     * PUT /api/employees/{employee}
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): EmployeeResource
    {
        $originalData = $employee->toArray();

        $employee->update($request->validated());

        $changedFields = array_keys(array_diff_assoc(
            $employee->toArray(),
            $originalData
        ));

        $this->eventPublisher->publishUpdated($employee, $changedFields, $employee->updated_at);

        return new EmployeeResource($employee);
    }

    /**
     * DELETE /api/employees/{employee}
     */
    public function destroy(Employee $employee): JsonResponse
    {
        $employeeData = $employee->toArray();

        $employee->delete();

        $this->eventPublisher->publishDeleted($employeeData, $employee->deleted_at);

        return response()->json(null, 204);
    }
}
