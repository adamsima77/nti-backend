<?php

use Illuminate\Support\Facades\Route;
use Modules\Students\app\Http\Controllers\StudentsController;
use Modules\Students\Http\Controllers\AcademicFlagController;
use Modules\Students\Http\Controllers\StudyFieldController;
use Modules\Students\Http\Controllers\StudyProgramController;
use Modules\Students\Http\Controllers\StudyYearController;
use Modules\Students\Http\Controllers\UniversityController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('students', StudentsController::class);
    Route::apiResource('university', UniversityController::class)->except(['index']);
    Route::apiResource('academic-flag', AcademicFlagController::class)->except(['index']);
    Route::apiResource('study-field', StudyFieldController::class)->except(['index']);
    Route::apiResource('study-program', StudyProgramController::class)->except(['index']);
});

Route::apiResource('university', UniversityController::class)->only(['index']);
Route::apiResource('academic-flag', AcademicFlagController::class)->only(['index']);
Route::apiResource('study-program', StudyProgramController::class)->only(['index']);
Route::apiResource('study-field', StudyFieldController::class)->only(['index']);
Route::apiResource('study-years', StudyYearController::class)->only(['index']);
