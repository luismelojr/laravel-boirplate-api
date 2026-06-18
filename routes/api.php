<?php

use App\Http\Controllers\Api\V1\Dashboard\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Admin-only routes
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        // Route::apiResource('users', UserController::class);
    });
});
