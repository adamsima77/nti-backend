<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\EmailTemplateController;

// Routes for module.
Route::get('/email-templates/lang/{lang}', [EmailTemplateController::class, 'fetchByLang']);
Route::get('/email-templates/cms/{id}',    [EmailTemplateController::class, 'showCms']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('email-templates', EmailTemplateController::class)
        ->only(['index', 'show', 'update']);
});
