<?php

use Illuminate\Support\Facades\Route;
use Modules\Programs\Http\Controllers\CallController;
use Modules\Programs\Http\Controllers\ProgramsController;

Route::get('calls', [CallController::class, 'index']);
Route::get('calls/{id}', [CallController::class, 'show']);

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('programs', ProgramsController::class)->names('programs');
});
