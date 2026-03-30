<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HerramientaController;



Route::get('/herramientas', [HerramientaController::class, 'index']);
Route::post('/herramientas', [HerramientaController::class, 'store']);
Route::get('/herramientas/{id}', [HerramientaController::class, 'show']);
Route::put('/herramientas/{id}', [HerramientaController::class, 'update']);
Route::delete('/herramientas/{id}', [HerramientaController::class, 'destroy']);

Route::post('/herramientas', [HerramientaController::class, 'store']);
Route::get('/herramientas', [HerramientaController::class, 'index']);

Route::middleware('throttle:5,1')->post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me', [AuthController::class, 'me']);
});
