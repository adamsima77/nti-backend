<?php

use Illuminate\Support\Facades\Route;
use Modules\Programs\Http\Controllers\CallController;
use Modules\Programs\Http\Controllers\CallWorkflowController;
use Modules\Programs\Http\Controllers\ProgramsController;
use Modules\Reporting\Http\Controllers\ExportController;

Route::get('/calls/{id}/lang/{lang}', [CallController::class, 'fetchCallByIdAndLang']);
Route::get('/programs/lang/{lang}', [ProgramsController::class, 'getProgramByLang']);
Route::get('/calls/lang/{lang}', [CallController::class, 'fetchCallByLang']);
Route::get('calls', [CallController::class, 'index']);
Route::get('calls/{id}', [CallController::class, 'show']);
Route::get('calls/{id}/pdf', [ExportController::class, 'callPdf'])->name('calls.pdf');
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('calls/export/{format?}', [ExportController::class, 'calls'])->name('calls.export');

    Route::prefix('v1')->group(function () {
        Route::apiResource('programs', ProgramsController::class)->names('programs');
        Route::apiResource('calls', CallController::class)->except('index', 'show')->names('calls');

        Route::get('calls/{call}/workflow', [CallWorkflowController::class, 'show']);
        Route::patch('calls/{call}/workflow', [CallWorkflowController::class, 'transition']);
    });
});
