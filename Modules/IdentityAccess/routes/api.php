<?php

use Illuminate\Support\Facades\Route;
use Modules\IdentityAccess\Http\Controllers\IdentityAccessController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('identityaccesses', IdentityAccessController::class)->names('identityaccess');
});
