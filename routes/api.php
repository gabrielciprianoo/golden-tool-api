<?php

use App\Http\Controllers\AsignationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\WorkerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (SIN autenticación)
|--------------------------------------------------------------------------
*/
Route::get('/inventory-check', [AsignationController::class, 'inventoryCheck']);
Route::post('/inventory-fix/{toolId}', [AsignationController::class, 'fixInventory']);

// 🔐 Login (con throttle para evitar ataques)
Route::middleware('throttle:5,1')->post('/login', [AuthController::class, 'login']);

// 📝 Requests
Route::get('/requests/worker/{workerId}', [RequestController::class, 'index']);
Route::post('/requests', [RequestController::class, 'store']);

// 🧪 Asignations (libre solo para pruebas)
Route::get('/asignations', [AsignationController::class, 'index']);
Route::post('/asignations', [AsignationController::class, 'store']);
Route::get('/asignations/{id}', [AsignationController::class, 'show']);
Route::delete('/asignations/{id}', [AsignationController::class, 'destroy']);
Route::put('/asignations/{id}', [AsignationController::class, 'update']);
Route::patch('/asignations/{id}', [AsignationController::class, 'update']);
Route::get('/asignations/worker/{workerId}', [AsignationController::class, 'getByWorker']);

/*
|--------------------------------------------------------------------------
| Protected Routes (requieren token - Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::middleware('isAdmin')->group(function () {
        Route::get('/tools', [ToolController::class, 'index']);
        Route::post('/tool', [ToolController::class, 'store']);
        Route::get('/tool/{id}', [ToolController::class, 'show']);
        Route::put('/tool/{id}', [ToolController::class, 'update']);
        Route::patch('/tool/{id}', [ToolController::class, 'update']);
        Route::delete('/tool/{id}', [ToolController::class, 'destroy']);

        Route::apiResource('workers', WorkerController::class);

        Route::get('/reviews', [ReviewController::class, 'index']);
        Route::post('/reviews', [ReviewController::class, 'store']);
    });

    Route::get('/workers', [WorkerController::class, 'index']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});
