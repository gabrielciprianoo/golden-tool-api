<?php

use App\Http\Controllers\WorkerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
 Route::get('workers', [WorkerController::class, 'index']);
 Route::post('workers', [WorkerController::class, 'store']);
 Route::put('workers/{worker}', [WorkerController::class, 'update']);
 Route::delete('workers/{worker}', [WorkerController::class, 'destroy']);
});
