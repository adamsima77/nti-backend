<?php

use Illuminate\Support\Facades\Route;
use Modules\Applications\Http\Controllers\ApplicationController;
use Modules\Applications\Http\Controllers\DocumentController;
use Modules\Reporting\Http\Controllers\ExportController;



Route::middleware('auth:sanctum')->group(function () {
    Route::get('applications/export/{format?}', [ExportController::class, 'applications'])->name('applications.export');
    Route::get('applications/{id}/pdf', [ExportController::class, 'applicationPdf'])->name('applications.pdf');
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::get('/applications/{id}', [ApplicationController::class, 'show']);
    Route::post('/applications', [ApplicationController::class, 'store']);
    Route::patch('/applications/{id}/status', [ApplicationController::class, 'updateStatus']);
});
