<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkerController;

Route::middleware('throttle:5,1')->post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('workers', WorkerController::class);
    Route::get('/me', [AuthController::class, 'me']);
});
