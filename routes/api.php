<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('api.auth.login')->middleware('throttle:5,1');

    // Refresh token endpoint accepts a refresh_token in the body (public)
    Route::post('refresh', [AuthController::class, 'refresh'])->name('api.auth.refresh');

    // Protected auth routes
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('api.auth.me');
    });
});

// Task routes (protected by JWT auth)
Route::middleware('auth:api')->group(function () {
    Route::apiResource('tasks', TaskController::class);
    Route::post('tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
    Route::post('tasks/{task}/incomplete', [TaskController::class, 'incomplete'])->name('tasks.incomplete');

    // Populate external tasks
    Route::post('tasks/populate', [TaskController::class, 'populate'])->name('tasks.populate');
});
