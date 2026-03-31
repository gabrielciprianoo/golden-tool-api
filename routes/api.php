<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkerController;

Route::middleware('throttle:5,1')->post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
     Route::get('workers', [WorkerController::class, 'index']);
 Route::post('workers', [WorkerController::class, 'store']);
 Route::put('workers/{worker}', [WorkerController::class, 'update']);
 Route::delete('workers/{worker}', [WorkerController::class, 'destroy']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('workers', WorkerController::class);
    Route::get('/me', [AuthController::class, 'me']);
});
