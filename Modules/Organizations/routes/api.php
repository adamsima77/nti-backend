<?php

use Illuminate\Support\Facades\Route;
use Modules\Organizations\Http\Controllers\OrganizationController;
use Modules\Organizations\Http\Controllers\ProductOwnerController;
use Modules\Organizations\Http\Controllers\SectorController;


Route::middleware(['auth:sanctum', 'verified', 'throttle:30,1'])->group(function () {


    Route::post('/organizations/{organization}/activate', [OrganizationController::class, 'activate']);
    Route::post('organizations/{organization}/members', [OrganizationController::class, 'inviteMember']);
    Route::patch('organizations/{organization}/members/{user}', [OrganizationController::class, 'updateMember']);
    Route::delete('organizations/{organization}/members/{user}', [OrganizationController::class, 'removeMember']);


    Route::apiResource('organizations', OrganizationController::class)->only(['store', 'update', 'destroy']);


    Route::prefix('po')->group(function () {
        Route::put('calls/{call}', [ProductOwnerController::class, 'updateCall']);
        Route::patch('calls/{call}/milestone-approvals/{milestone}/approve', [ProductOwnerController::class, 'approveMilestone']);
        Route::patch('calls/{call}/milestone-approvals/{milestone}/reject', [ProductOwnerController::class, 'rejectMilestone']);
    });
});

Route::middleware(['auth:sanctum', 'verified', 'throttle:120,1'])->group(function () {


    Route::get('my-organization', [OrganizationController::class, 'myOrganization']);
    Route::get('organizations/{organization}/backlog', [OrganizationController::class, 'backlog']);
    Route::get('organizations/{organization}/member-dashboard', [OrganizationController::class, 'memberDashboard']);
    Route::apiResource('organizations', OrganizationController::class)->only(['index', 'show']);


    Route::prefix('po')->group(function () {
        Route::get('dashboard', [ProductOwnerController::class, 'dashboard']);
        Route::get('calls/{call}/milestone-approvals', [ProductOwnerController::class, 'milestoneApprovals']);
    });
});

Route::middleware(['throttle:60,1'])->group(function () {
    Route::apiResource('sectors', SectorController::class)->only('index');
    Route::get('/sectors/lang/{lang}', [SectorController::class, 'getSectorByLang']);
});
