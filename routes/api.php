<?php

use App\Http\Controllers\Api\V1\Dashboard\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Dashboard\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public — no tenant required
    Route::post('register', [AuthController::class, 'register']);

    // All routes below require a valid X-Tenant-ID header
    Route::middleware('ensure_tenant')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::post('invite/accept', [AuthController::class, 'acceptInvite']);

        Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->name('verification.verify');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::post('email/resend', [AuthController::class, 'resendVerification']);
        });

        // Admin-only routes
        Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
            Route::post('users/invite', [AdminUserController::class, 'invite']);
        });
    });
});
