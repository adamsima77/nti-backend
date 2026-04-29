<?php

use Illuminate\Support\Facades\Route;
use Modules\Evaluation\Http\Controllers\EvaluationController;

Route::middleware('auth:sanctum')->group(function () {
	Route::get('/evaluations/pending', [EvaluationController::class, 'pending']);
    Route::post('evaluations/{application_id}/score', [EvaluationController::class, 'storeScore']);
});
