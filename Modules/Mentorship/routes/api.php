<?php

use Illuminate\Support\Facades\Route;
use Modules\Mentorship\Http\Controllers\MentorshipController;
use Modules\Mentorship\Http\Controllers\MilestoneController;

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('mentor')->group(function () {
        Route::get('dashboard', [MentorshipController::class, 'dashboard']);
        Route::get('projects', [MentorshipController::class, 'projects']);
        Route::get('consultations', [MentorshipController::class, 'consultations']);
        Route::get('projects/{project}/milestones', [MentorshipController::class, 'projectMilestones']);
        Route::patch('projects/{project}/milestones/{milestone}', [MentorshipController::class, 'updateMilestone']);
        Route::get('projects/{project}/consultations', [MentorshipController::class, 'projectConsultations']);
        Route::post('projects/{project}/consultations', [MentorshipController::class, 'storeConsultation']);
        Route::post('projects/{project}/feedback', [MentorshipController::class, 'storeFeedback']);
        Route::post('/admin/applications/{application}/mentors/{user}', [MentorshipController::class, 'assignMentor']);
    });

    Route::get('/mentors', [MentorshipController::class, 'fetchMentors']);

    Route::apiResource('milestones', MilestoneController::class);
});
