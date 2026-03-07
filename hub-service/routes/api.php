<?php

use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\SchemaController;
use App\Http\Controllers\Api\StepsController;
use Illuminate\Support\Facades\Route;

Route::get('/checklists', [ChecklistController::class, 'index']);
Route::get('/steps', [StepsController::class, 'index']);
Route::get('/employees', [EmployeeController::class, 'index']);
Route::get('/schema/{step_id}', [SchemaController::class, 'show']);

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'service' => 'hub-service']);
});
