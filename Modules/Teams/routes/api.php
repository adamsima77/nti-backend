<?php

use Illuminate\Support\Facades\Route;
use Modules\Teams\Http\Controllers\TeamsController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('teams', TeamsController::class);
    Route::post('teams/{team}/members', [TeamsController::class, 'addMember']);
    Route::delete('teams/{team}/members/{user}', [TeamsController::class, 'removeMember']);
});
