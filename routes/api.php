<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Middleware\ApiKeyAuthMiddleware;
use Illuminate\Support\Facades\Route;

/*
||--------------------------------------------------------------------------
| API Routes
||--------------------------------------------------------------------------
|
| Aqui se registran las rutas de la API. Todas comparten el prefijo "api"
| definido en el RouteServiceProvider.
|
*/

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('api.auth.login')->middleware('throttle:5,1');
    Route::post('refresh', [AuthController::class, 'refresh'])->name('api.auth.refresh')->middleware('throttle:5,1');

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('api.auth.me');
    });
});

Route::middleware('auth:api')->group(function () {
    Route::middleware(ApiKeyAuthMiddleware::class)->group(function () {
        Route::get('tasks/populate', [TaskController::class, 'populate'])
            ->name('tasks.populate');
    });

    Route::apiResource('tasks', TaskController::class)
        ->where(['task' => '[0-9a-fA-F\\-]{36}']);

    Route::post('tasks/{task}/complete', [TaskController::class, 'complete'])
        ->where(['task' => '[0-9a-fA-F\\-]{36}'])
        ->name('tasks.complete');

    Route::post('tasks/{task}/incomplete', [TaskController::class, 'incomplete'])
        ->where(['task' => '[0-9a-fA-F\\-]{36}'])
        ->name('tasks.incomplete');
});
