<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HerramientaController;
use Illuminate\Support\Facades\Route;

Route::get('/tools', [HerramientaController::class, 'index']);
Route::post('/tool', [HerramientaController::class, 'store']);
Route::get('/tool/{id}', [HerramientaController::class, 'show']);
Route::put('/tool/{id}', [HerramientaController::class, 'update']);
Route::patch('/tool/{id}', [HerramientaController::class, 'update']);
Route::delete('/tool/{id}', [HerramientaController::class, 'destroy']);

Route::middleware('throttle:5,1')->post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', [AuthController::class, 'me']);
});
