<?php

use Illuminate\Support\Facades\Route;
use Modules\Mentorship\Http\Controllers\MilestoneController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('milestones', MilestoneController::class);
});
