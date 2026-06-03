<?php

use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\IdentityAccess\Http\Controllers\ConsentTypeController;
use Modules\IdentityAccess\Http\Controllers\RoleController;
use Modules\IdentityAccess\Http\Controllers\StatusController;
use Modules\IdentityAccess\Http\Controllers\UserConsentController;
use Modules\IdentityAccess\Http\Controllers\UserController;
use Modules\IdentityAccess\Http\Controllers\AuthController;
use Modules\Organizations\Http\Controllers\OrganizationController;
use Modules\Reporting\Http\Controllers\ExportController;
use Illuminate\Http\Request;
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:auth.login');
    Route::post('register', [AuthController::class, 'register'])
        ->middleware('throttle:auth.register');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:auth.forgot');
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('resend-verification', [AuthController::class, 'resendNotification']);
    Route::get('verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/organization-onboarding', [AuthController::class, 'organizationOnboarding']);
    Route::post('auth/student-onboarding', [AuthController::class, 'studentOnboarding']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/roles-permissions', [RoleController::class, 'fetchRolesPermissions']);
    Route::post('/sync-permissions/{role}/permissions', [RoleController::class, 'syncPermissions']);
    Route::get('profile', [UserController::class, 'profile']);
    Route::match(['put', 'patch'], 'profile', [UserController::class, 'updateProfile']);
    Route::post('profile/avatar', [UserController::class, 'uploadCurrentAvatar']);
    Route::get('users/export/{format?}', [ExportController::class, 'users'])->name('users.export');
    Route::get('users/{user}/pdf', [ExportController::class, 'userPdf'])->name('users.pdf')->whereNumber('user');
    // POST: multipart súborov — PUT s FormData v PHP často nevyplní $_FILES; profilová fotka ide cez túto cestu.
    Route::post('users/{user}/avatar',       [UserController::class, 'uploadAvatar'])->whereNumber('user');
    Route::post('users/{user}/student',      [UserController::class, 'createStudentProfile'])->whereNumber('user');
    Route::post('users/{user}/organization', [UserController::class, 'createOrganizationProfile'])->whereNumber('user');
    Route::post('/users/anonymize-user/{id}', [UserController::class, 'anonymizeUser']);
    Route::apiResource('users', UserController::class)->only(['index', 'store', 'show', 'update', 'destroy'])->whereNumber('user');
    Route::apiResource('consent-types', ConsentTypeController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::apiResource('roles', RoleController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::apiResource('statuses', StatusController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::apiResource('user-consents', UserConsentController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
});

Route::get('fetch-mentors', [UserController::class, 'getMentors']);



