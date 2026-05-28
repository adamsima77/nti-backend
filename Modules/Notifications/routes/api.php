<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\EmailTemplateController;
use Modules\Notifications\Http\Controllers\NotificationController;

// Routes for module.
Route::get('/email-templates/lang/{lang}', [EmailTemplateController::class, 'fetchByLang']);
Route::get('/email-templates/cms/{id}',    [EmailTemplateController::class, 'showCms']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('email-templates', EmailTemplateController::class)
        ->only(['index', 'show', 'update']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead']);
});
