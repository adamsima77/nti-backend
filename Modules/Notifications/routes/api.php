<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\EmailTemplateController;
use Modules\Notifications\Http\Controllers\NotificationController;

Route::middleware(['auth:sanctum', 'verified', 'throttle:3,1'])->group(function () {
    Route::post('/send-bulk-email', [NotificationController::class, 'sendBulkEmail']);
});

Route::middleware(['auth:sanctum', 'verified', 'throttle:60,1'])->group(function () {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead']);
});

Route::middleware(['auth:sanctum', 'verified', 'throttle:120,1'])->group(function () {

    Route::get('/email-templates/lang/{lang}', [EmailTemplateController::class, 'fetchByLang']);
    Route::get('/email-templates/cms/{id}',    [EmailTemplateController::class, 'showCms']);

    Route::get('/fetch-all-templates', [EmailTemplateController::class, 'fetchAll']);
    Route::apiResource('email-templates', EmailTemplateController::class)
        ->only(['index', 'show', 'update']);
});
