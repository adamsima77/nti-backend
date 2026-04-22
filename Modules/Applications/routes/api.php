<?php

use Illuminate\Support\Facades\Route;
use Modules\Applications\Http\Controllers\ApplicationController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::get('/applications/{id}', [ApplicationController::class, 'show']);
    Route::post('/applications', [ApplicationController::class, 'store']);
    Route::patch('/applications/{id}/status', [ApplicationController::class, 'updateStatus']);
});
