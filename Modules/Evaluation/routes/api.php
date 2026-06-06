<?php

use Illuminate\Support\Facades\Route;
use Modules\Applications\Http\Controllers\ApplicationController;
use Modules\Evaluation\Http\Controllers\EvaluationController;

Route::middleware('auth:sanctum')->group(function () {
	Route::get('/evaluations/pending', [EvaluationController::class, 'pending']);
    Route::post('evaluations/{application_id}/score', [EvaluationController::class, 'storeScore']);

	Route::prefix('evaluator')->group(function () {
		Route::get('dashboard', [EvaluationController::class, 'dashboard']);
		Route::get('calls', [EvaluationController::class, 'calls']);
		Route::get('calls/{callId}/applications', [EvaluationController::class, 'callApplications']);
		Route::get('applications/{applicationId}', [EvaluationController::class, 'application']);
		Route::get('applications/{applicationId}/evaluations', [EvaluationController::class, 'index']);
		Route::post('applications/{applicationId}/evaluations', [EvaluationController::class, 'storeEvaluatorEvaluation']);
		Route::patch('applications/{applicationId}/evaluations/{evaluationId}', [EvaluationController::class, 'updateEvaluatorEvaluation']);
		Route::post('applications/{applicationId}/supplement-request', [EvaluationController::class, 'requestSupplement']);
        Route::post('/admin/applications/{application}/commissions', [EvaluationController::class, 'assignCommission']);
       Route::get('/fetch-commissions', [EvaluationController::class, 'fetchCommittes']);
    });
});
