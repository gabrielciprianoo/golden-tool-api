<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\WorkerController;
use App\Http\Controllers\AsignationController;

/*
|--------------------------------------------------------------------------
| Public Routes (SIN autenticación)
|--------------------------------------------------------------------------
*/
Route::get('/inventory-check', [AsignationController::class, 'inventoryCheck']);
Route::post('/inventory-fix/{toolId}', [AsignationController::class, 'fixInventory']);

// 🔐 Login (con throttle para evitar ataques)
Route::middleware('throttle:5,1')->post('/login', [AuthController::class, 'login']);

// 🧪 Asignations (libre solo para pruebas)
Route::get('/asignations', [AsignationController::class, 'index']);
Route::post('/asignations', [AsignationController::class, 'store']);
Route::get('/asignations/{id}', [AsignationController::class, 'show']);
Route::delete('/asignations/{id}', [AsignationController::class, 'destroy']);


/*
|--------------------------------------------------------------------------
| Protected Routes (requieren token - Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // 🔧 Tools
    Route::get('/tools', [ToolController::class, 'index']);
    Route::post('/tool', [ToolController::class, 'store']);
    Route::get('/tool/{id}', [ToolController::class, 'show']);
    Route::put('/tool/{id}', [ToolController::class, 'update']);
    Route::patch('/tool/{id}', [ToolController::class, 'update']);
    Route::delete('/tool/{id}', [ToolController::class, 'destroy']);

    // 👷 Workers (REST completo)
    Route::apiResource('workers', WorkerController::class);

    // 🔐 Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});