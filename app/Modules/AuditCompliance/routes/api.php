<?php

use Illuminate\Support\Facades\Route;
use Modules\AuditCompliance\Http\Controllers\GdprReportController;

Route::middleware(['auth:sanctum', 'verified', 'throttle:audit'])->group(function () {


    Route::post('/gdpr-reports/generate-report', [GdprReportController::class, 'generateGdprReport'])
        ->middleware('throttle:20,1')
        ->name('gdpr-reports.generate');


    Route::get('/gdpr-reports/{report}', [GdprReportController::class, 'show'])
        ->name('gdpr-reports.show');


    Route::get('/gdpr-reports/{report}/download', [GdprReportController::class, 'download'])
        ->middleware('throttle:20,1')
        ->name('gdpr-reports.download');
});


