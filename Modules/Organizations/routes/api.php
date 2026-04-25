<?php

use Illuminate\Support\Facades\Route;
use Modules\Organizations\Http\Controllers\OrganizationController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('organizations', OrganizationController::class);
});
