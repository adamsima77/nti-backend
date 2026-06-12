<?php

use Illuminate\Support\Facades\Route;
use Modules\IdentityAccess\Http\Controllers\ConsentTypeController;
use Modules\IdentityAccess\Http\Controllers\RoleController;
use Modules\IdentityAccess\Http\Controllers\StatusController;
use Modules\IdentityAccess\Http\Controllers\UserConsentController;
use Modules\IdentityAccess\Http\Controllers\UserController;
use Modules\IdentityAccess\Http\Controllers\AuthController;
use Modules\Evaluation\Http\Controllers\AcceptCommissionInviteController;
use Modules\Organizations\Http\Controllers\AcceptInviteController;
use Modules\Reporting\Http\Controllers\ExportController;


Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth.login');
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth.register');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth.forgot');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:6,1');
    Route::post('resend-verification', [AuthController::class, 'resendNotification'])
    ->middleware('throttle:5,1');

    // Signed email verification link (6 attempts per minute limit)
    Route::get('verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Token/Invitation routes
    Route::get('invite', [AcceptInviteController::class, 'show'])->middleware(['throttle:10,1']);
    Route::post('accept-invite', [AcceptInviteController::class, 'accept'])->middleware(['throttle:10,1']);
    Route::get('commission-invite', [AcceptCommissionInviteController::class, 'show'])->middleware(['throttle:10,1']);
    Route::post('accept-commission-invite', [AcceptCommissionInviteController::class, 'accept'])->middleware(['throttle:10,1']);
});


Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']); // Used to detect verification state
    Route::post('auth/organization-onboarding', [AuthController::class, 'organizationOnboarding']);
    Route::post('auth/student-onboarding', [AuthController::class, 'studentOnboarding']);
});


Route::middleware(['auth:sanctum', 'verified', 'throttle:20,1'])->group(function () {
    Route::get('users/export/{format?}', [ExportController::class, 'users'])->name('users.export');
    Route::get('users/{user}/pdf', [ExportController::class, 'userPdf'])->name('users.pdf')->whereNumber('user');
});


Route::middleware(['auth:sanctum', 'verified', 'throttle:120,1'])->group(function () {
    // Roles & Permissions
    Route::get('/roles-permissions', [RoleController::class, 'fetchRolesPermissions']);
    Route::post('/sync-permissions/{role}/permissions', [RoleController::class, 'syncPermissions']);

    // Core Profiles
    Route::get('profile', [UserController::class, 'profile']);
    Route::match(['put', 'patch'], 'profile', [UserController::class, 'updateProfile']);
    Route::post('profile/avatar', [UserController::class, 'uploadCurrentAvatar']);

    // User Modifications & Sub-resource Profiles
    Route::post('users/{user}/avatar',       [UserController::class, 'uploadAvatar'])->whereNumber('user');
    Route::post('users/{user}/student',      [UserController::class, 'createStudentProfile'])->whereNumber('user');
    Route::post('users/{user}/organization', [UserController::class, 'createOrganizationProfile'])->whereNumber('user');
    Route::post('/users/anonymize-user/{id}', [UserController::class, 'anonymizeUser']);

    // API Resources
    Route::apiResource('users', UserController::class)->only(['index', 'store', 'show', 'update', 'destroy'])->whereNumber('user');
    Route::apiResource('consent-types', ConsentTypeController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::apiResource('roles', RoleController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::apiResource('statuses', StatusController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::apiResource('user-consents', UserConsentController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
});

Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('fetch-mentors', [UserController::class, 'getMentors']);
});
