<?php

use Illuminate\Support\Facades\Route;

use Modules\Students\Http\Controllers\StudentDashboardController;
use Modules\Students\Http\Controllers\StudentsController;
use Modules\Students\Http\Controllers\AcademicFlagController;
use Modules\Students\Http\Controllers\StudyFieldController;
use Modules\Students\Http\Controllers\StudyProgramController;
use Modules\Students\Http\Controllers\StudyYearController;
use Modules\Students\Http\Controllers\UniversityController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('students/me', [StudentsController::class, 'showMe']);
    Route::get('student/academic-record', [StudentsController::class, 'academicRecordMe']);
    Route::post('student/academic-record', [StudentsController::class, 'storeAcademicRecord']);
    Route::get('students/{student}/academic-record', [StudentsController::class, 'academicRecord']);

    Route::apiResource('students', StudentsController::class);
    Route::apiResource('university', UniversityController::class);
    Route::apiResource('academic-flag', AcademicFlagController::class);
    Route::apiResource('study-field', StudyFieldController::class);
    Route::apiResource('study-program', StudyProgramController::class);
    Route::apiResource('study-year', StudyYearController::class)->only(['index']);
    Route::get('/get-academic-record/{document}', [StudentsController::class, 'downloadRecord']);
});
Route::get('/study-fields-public/lang/{lang}', [StudyFieldController::class, 'fetchByLangPublic']);
Route::get('/study-years-public/lang/{lang}', [StudyYearController::class, 'fetchByLangPublic']);
Route::get('/study-programs-public/lang/{lang}', [StudyProgramController::class, 'fetchByLangPublic']);

Route::get('/v1/student/dashboard', StudentDashboardController::class)
    ->middleware('auth:sanctum');
