<?php

use Illuminate\Support\Facades\Route;
use Modules\Teams\Http\Controllers\TeamsController;
use Modules\Reporting\Http\Controllers\ExportController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('teams/{team}/pdf', [ExportController::class, 'teamPdf'])->name('teams.pdf');
    Route::post('teams/invitations/accept', [TeamsController::class, 'acceptInvitation']);
    Route::apiResource('teams', TeamsController::class);
    Route::post('teams/{team}/members', [TeamsController::class, 'addMember']);
    Route::post('teams/{team}/invite', [TeamsController::class, 'invite']);
    Route::delete('teams/{team}/members/{user}', [TeamsController::class, 'removeMember']);
});
