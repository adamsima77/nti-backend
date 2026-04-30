<?php

use Illuminate\Support\Facades\Route;
use Modules\Teams\Http\Controllers\TeamsController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('teams/{team}/pdf', [TeamsController::class, 'downloadPdf'])->name('teams.pdf');
    Route::apiResource('teams', TeamsController::class);
    Route::post('teams/{team}/members', [TeamsController::class, 'addMember']);
    Route::delete('teams/{team}/members/{user}', [TeamsController::class, 'removeMember']);
});
