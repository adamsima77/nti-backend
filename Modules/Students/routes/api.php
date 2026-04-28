<?php

use Illuminate\Support\Facades\Route;
use Modules\Students\app\Http\Controllers\StudentsController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('students', StudentsController::class);
});
