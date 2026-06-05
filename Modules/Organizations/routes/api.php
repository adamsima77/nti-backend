<?php

use Illuminate\Support\Facades\Route;
use Modules\Organizations\Http\Controllers\OrganizationController;
use Modules\Organizations\Http\Controllers\SectorController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('organizations', OrganizationController::class);
    Route::get('organizations/{organization}/backlog', [OrganizationController::class, 'backlog']);
    Route::post('organizations/{organization}/members', [OrganizationController::class, 'inviteMember']);
    Route::patch('organizations/{organization}/members/{user}', [OrganizationController::class, 'updateMember']);
    Route::delete('organizations/{organization}/members/{user}', [OrganizationController::class, 'removeMember']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/organizations/{organization}/activate', [OrganizationController::class, 'activate']);
});
Route::apiResource('sectors', SectorController::class)->only('index');
Route::get('/sectors/lang/{lang}', [SectorController::class, 'getSectorByLang']);
