<?php

use Illuminate\Support\Facades\Route;
use Modules\Teams\Http\Controllers\TeamsController;
use Modules\Reporting\Http\Controllers\ExportController;


Route::middleware(['auth:sanctum', 'verified', 'throttle:30,1'])->group(function () {
    Route::get('teams/{team}/pdf', [ExportController::class, 'teamPdf'])->name('teams.pdf');
});


Route::middleware(['auth:sanctum', 'verified', 'throttle:30,1'])->group(function () {

    // Invitation Lifecycles
    Route::post('teams/invitations/accept', [TeamsController::class, 'acceptInvitation']);
    Route::post('teams/{team}/invite', [TeamsController::class, 'invite']);

    // Roster and Membership Matrix Updates
    Route::post('teams/{team}/members', [TeamsController::class, 'addMember']);
    Route::delete('teams/{team}/members/{user}', [TeamsController::class, 'removeMember']);

    // Core Workspace Modifiers
    Route::apiResource('teams', TeamsController::class)->only(['store', 'update', 'destroy']);
});


Route::middleware(['auth:sanctum', 'verified', 'throttle:120,1'])->group(function () {
    Route::apiResource('teams', TeamsController::class)->only(['index', 'show']);
});
