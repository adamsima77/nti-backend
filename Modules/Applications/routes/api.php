<?php

use Illuminate\Support\Facades\Route;
use Modules\Applications\Http\Controllers\ApplicationController;
use Modules\Applications\Http\Controllers\DocumentController;
use Modules\Applications\Http\Controllers\StatusOfApplicationController;
use Modules\Evaluation\Http\Controllers\EvaluationController;
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
    Route::put('/applications/{id}', [ApplicationController::class, 'update']);
    Route::patch('/applications/{id}', [ApplicationController::class, 'update']);
    Route::post('/applications/{id}/submit', [ApplicationController::class, 'submit']);
    Route::get('/admin/applications', [ApplicationController::class, 'fetchForAdmin']);
    Route::get('/status-of-applications', [StatusOfApplicationController::class, 'index']);
    Route::get('/application-answer/{application}', [ApplicationController::class, 'getApplicationAnswer']);
    Route::post('/applications/draft', [ApplicationController::class, 'storeDraft']);
    Route::post('/change-app-state/{application}/admin', [ApplicationController::class, 'updateStateAdmin']);
    Route::get('/admin-app-statuses', [StatusOfApplicationController::class, 'fetchAdminStatuses']);
    Route::delete('/remove-committee/{application}', [ApplicationController::class, 'removeCommittee']);
    Route::post('/add-committee/{application}/committee/{committee}', [ApplicationController::class, 'addCommittee']);
    Route::delete('/admin/applications/{application}/mentorships/{mentorship}', [ApplicationController::class, 'deleteMentor']);
    Route::post('/submit-application', [ApplicationController::class, 'submitApplication']);
    Route::get('/get-status-admin', [StatusOfApplicationController::class, 'fetchExceptDraftAdmin']);

    Route::get('/fetch-for-evaluation', [EvaluationController::class, 'fetchForEvaluation']);
});
