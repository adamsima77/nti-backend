<?php

use Illuminate\Support\Facades\Route;
use Modules\Organizations\Http\Controllers\OrganizationController;
use Modules\Organizations\Http\Controllers\ProductOwnerController;
use Modules\Organizations\Http\Controllers\SectorController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('organizations', OrganizationController::class);
    Route::get('my-organization', [OrganizationController::class, 'myOrganization']);
    Route::get('organizations/{organization}/backlog', [OrganizationController::class, 'backlog']);
    Route::get('organizations/{organization}/member-dashboard', [OrganizationController::class, 'memberDashboard']);
    Route::post('organizations/{organization}/members', [OrganizationController::class, 'inviteMember']);
    Route::patch('organizations/{organization}/members/{user}', [OrganizationController::class, 'updateMember']);
    Route::delete('organizations/{organization}/members/{user}', [OrganizationController::class, 'removeMember']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/organizations/{organization}/activate', [OrganizationController::class, 'activate']);
});
Route::middleware('auth:sanctum')->prefix('po')->group(function () {
    Route::get('dashboard', [ProductOwnerController::class, 'dashboard']);
    Route::get('calls/{call}/backlog', [ProductOwnerController::class, 'backlog']);
    Route::post('calls/{call}/backlog', [ProductOwnerController::class, 'storeBacklogItem']);
    Route::patch('calls/{call}/backlog/{milestone}', [ProductOwnerController::class, 'updateBacklogItem']);
    Route::delete('calls/{call}/backlog/{milestone}', [ProductOwnerController::class, 'deleteBacklogItem']);
    Route::get('calls/{call}/milestone-approvals', [ProductOwnerController::class, 'milestoneApprovals']);
    Route::patch('calls/{call}/milestone-approvals/{milestone}/approve', [ProductOwnerController::class, 'approveMilestone']);
    Route::get('calls/{call}/documents', [ProductOwnerController::class, 'documents']);
    Route::post('calls/{call}/documents', [ProductOwnerController::class, 'uploadDocument']);
});

Route::apiResource('sectors', SectorController::class)->only('index');
Route::get('/sectors/lang/{lang}', [SectorController::class, 'getSectorByLang']);
