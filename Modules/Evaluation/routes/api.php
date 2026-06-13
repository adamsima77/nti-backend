<?php

use Illuminate\Support\Facades\Route;
use Modules\Applications\Http\Controllers\ApplicationController;
use Modules\Evaluation\Http\Controllers\CommissionController;
use Modules\Evaluation\Http\Controllers\EvaluationController;

Route::middleware(['auth:sanctum', 'verified'])->group(function () {

    Route::middleware(['throttle:200,1'])->prefix('v1/admin/commissions')->group(function () {
        Route::get('/',                           [CommissionController::class, 'index']);
        Route::post('/',                          [CommissionController::class, 'store']);
        Route::put('/{id}',                       [CommissionController::class, 'update']);
        Route::delete('/{id}',                    [CommissionController::class, 'destroy']);
        Route::post('/{id}/members',              [CommissionController::class, 'addMember']);
        Route::delete('/{id}/members/{memberId}', [CommissionController::class, 'removeMember']);
        Route::get('/evaluators',                 [CommissionController::class, 'evaluators']);
    });
    Route::middleware(['throttle:200,1'])->group(function () {
        Route::get('/evaluations/pending', [EvaluationController::class, 'pending']);
        Route::post('evaluations/{application_id}/score', [EvaluationController::class, 'storeScore']);
        Route::prefix('evaluator')->group(function () {
            Route::get('dashboard', [EvaluationController::class, 'dashboard']);
            Route::get('applications/{applicationId}', [EvaluationController::class, 'application']);
            Route::get('applications/{applicationId}/evaluations', [EvaluationController::class, 'index']);
            Route::get('fetch-for-evaluator', [EvaluationController::class, 'fetchForEvaluator']);
            Route::get('/fetch-commissions', [EvaluationController::class, 'fetchCommittes']);
            Route::post('applications/{applicationId}/evaluations', [EvaluationController::class, 'storeEvaluatorEvaluation']);
            Route::patch('applications/{applicationId}/evaluations/{evaluationId}', [EvaluationController::class, 'updateEvaluatorEvaluation']);
            Route::post('applications/{applicationId}/supplement-request', [EvaluationController::class, 'requestSupplement']);
            Route::post('/admin/applications/{application}/commissions', [EvaluationController::class, 'assignCommission']);
        });
    });
});
