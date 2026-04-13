<?php

use Illuminate\Support\Facades\Route;
use Modules\Programs\Http\Controllers\ProgramsController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('programs', ProgramsController::class)->names('programs');
});
