<?php

use Illuminate\Support\Facades\Route;
use Modules\Students\Http\Controllers\AcademicFlagController;
use Modules\Students\Http\Controllers\StudentDashboardController;
use Modules\Students\Http\Controllers\StudentsController;
use Modules\Students\Http\Controllers\StudyFieldController;
use Modules\Students\Http\Controllers\StudyProgramController;
use Modules\Students\Http\Controllers\StudyYearController;
use Modules\Students\Http\Controllers\UniversityController;


Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/study-fields-public/lang/{lang}', [StudyFieldController::class, 'fetchByLangPublic']);
    Route::get('/study-years-public/lang/{lang}', [StudyYearController::class, 'fetchByLangPublic']);
    Route::get('/study-programs-public/lang/{lang}', [StudyProgramController::class, 'fetchByLangPublic']);
});


Route::middleware(['auth:sanctum', 'verified', 'throttle:30,1'])->group(function () {
    Route::get('/get-academic-record/{document}', [StudentsController::class, 'downloadRecord']);
});


Route::middleware(['auth:sanctum', 'verified', 'throttle:120,1'])->group(function () {

    // Academic Records Modifications
    Route::post('student/academic-record', [StudentsController::class, 'storeAcademicRecord']);

    // Resource Mutators (Store, Update, Destroy)
    Route::apiResource('students', StudentsController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('university', UniversityController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('academic-flag', AcademicFlagController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('study-field', StudyFieldController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('study-program', StudyProgramController::class)->only(['store', 'update', 'destroy']);
});


Route::middleware(['auth:sanctum', 'verified', 'throttle:200,1'])->group(function () {

    // Profile Identity Lookups
    Route::get('students/me', [StudentsController::class, 'showMe']);
    Route::get('student/academic-record', [StudentsController::class, 'academicRecordMe']);
    Route::get('students/{student}/academic-record', [StudentsController::class, 'academicRecord']);
    Route::get('/v1/student/dashboard', StudentDashboardController::class);

    // Structural Configuration Indexes
    Route::apiResource('students', StudentsController::class)->only(['index', 'show']);
    Route::apiResource('university', UniversityController::class)->only(['index', 'show']);
    Route::apiResource('academic-flag', AcademicFlagController::class)->only(['index', 'show']);
    Route::apiResource('study-field', StudyFieldController::class)->only(['index', 'show']);
    Route::apiResource('study-program', StudyProgramController::class)->only(['index', 'show']);
    Route::apiResource('study-year', StudyYearController::class)->only(['index']);
});
