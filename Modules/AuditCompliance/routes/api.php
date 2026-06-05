<?php

use Illuminate\Support\Facades\Route;
use Modules\AuditCompliance\Http\Controllers\GdprReportController;

Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::post('/gdpr-reports/generate-report', [GdprReportController::class, 'generateGdprReport'])
        ->name('gdpr-reports.generate');

    // Polled by the frontend every ~3 s to check job status
    Route::get('/gdpr-reports/{report}', [GdprReportController::class, 'show'])
        ->name('gdpr-reports.show');

    Route::get('/gdpr-reports/{report}/download', [GdprReportController::class, 'download'])
        ->name('gdpr-reports.download');
});


