<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ToolController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkerController;

Route::middleware('throttle:5,1')->post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/tools', [ToolController::class, 'index']);
    Route::post('/tool', [ToolController::class, 'store']);
    Route::get('/tool/{id}', [ToolController::class, 'show']);
    Route::put('/tool/{id}', [ToolController::class, 'update']);
    Route::patch('/tool/{id}', [ToolController::class, 'update']);
    Route::delete('/tool/{id}', [ToolController::class, 'destroy']);

    Route::apiResource('workers', WorkerController::class);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});
