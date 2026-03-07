<?php

use App\Http\Controllers\Api\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::apiResource('employees', EmployeeController::class);

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'service' => 'hr-service']);
});
