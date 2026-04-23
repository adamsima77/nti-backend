<?php

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use Modules\IdentityAccess\Http\Controllers\ConsentTypeController;
use Modules\IdentityAccess\Http\Controllers\RoleController;
use Modules\IdentityAccess\Http\Controllers\StatusController;
use Modules\IdentityAccess\Http\Controllers\UserConsentController;
use Modules\IdentityAccess\Http\Controllers\UserController;
use Modules\IdentityAccess\Http\Controllers\AuthController;
use Modules\IdentityAccess\Models\User;

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::apiResource('users', UserController::class);
});

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('register', [AuthController::class, 'register']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::apiResource('consent-types', ConsentTypeController::class);
    Route::apiResource('roles', RoleController::class);
    Route::apiResource('statuses', StatusController::class);
    Route::apiResource('user-consents', UserConsentController::class);
});


Route::post('/auth/resend-verification', [AuthController::class, 'resendNotification']);
Route::get('/auth/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
