<?php

use Illuminate\Support\Facades\Route;
use Modules\Organizations\Http\Controllers\OrganizationController;
use Modules\Organizations\Http\Controllers\SectorController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('organizations', OrganizationController::class);
    Route::get('organizations/{organization}/backlog', [OrganizationController::class, 'backlog']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/organizations/{organization}/activate', [OrganizationController::class, 'activate']);
});
Route::apiResource('sectors', SectorController::class)->only('index');
