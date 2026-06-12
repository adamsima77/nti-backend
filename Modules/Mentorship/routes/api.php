<?php

use Illuminate\Support\Facades\Route;
use Modules\Mentorship\Http\Controllers\MentorshipController;
use Modules\Mentorship\Http\Controllers\CallMilestoneController;
use Modules\Mentorship\Http\Controllers\MilestoneController;
use Modules\Mentorship\Http\Controllers\MilestoneDocumentController;

Route::middleware(['auth:sanctum', 'verified'])->group(function () {


    Route::middleware(['throttle:35,1'])->group(function () {
        Route::get('calls/{call}/milestones/{milestone}/documents/{document}/download', [MilestoneDocumentController::class, 'download']);
    });


    Route::middleware(['throttle:30,1'])->group(function () {

        Route::delete('calls/{call}/milestones/{milestone}', [CallMilestoneController::class, 'destroy']);
        Route::delete('calls/{call}/milestones/{milestone}/documents/{document}', [MilestoneDocumentController::class, 'destroy']);
        Route::apiResource('milestones', MilestoneController::class)->except(['index', 'show']);


        Route::put('/update-milestone/{milestone}', [MilestoneController::class, 'studentAnswer']);

        Route::prefix('mentor')->group(function () {
            Route::post('projects/{project}/consultations', [MentorshipController::class, 'storeConsultation']);
            Route::post('projects/{project}/feedback', [MentorshipController::class, 'storeFeedback']);
            Route::post('/admin/applications/{application}/mentors/{user}', [MentorshipController::class, 'assignMentor']);
            Route::put('projects/{project}/consultations/{session}', [MentorshipController::class, 'updateConsultation']);
            Route::delete('projects/{project}/consultations/{session}', [MentorshipController::class, 'deleteConsultation']);
        });
    });

    Route::middleware(['throttle:100,1'])->group(function () {


        Route::get('/mentors', [MentorshipController::class, 'fetchMentors']);
        Route::get('/fetch-student-milestones', [MilestoneController::class, 'fetchMilestonesForStudent']);
        Route::apiResource('milestones', MilestoneController::class)->only(['index', 'show']);

        Route::prefix('mentor')->group(function () {
            Route::get('dashboard', [MentorshipController::class, 'dashboard']);
            Route::get('projects', [MentorshipController::class, 'projects']);
            Route::get('projects/{project}', [MentorshipController::class, 'projectDetail']); // Kept exactly one version
            Route::get('consultations', [MentorshipController::class, 'consultations']);
            Route::get('projects/{project}/milestones', [MentorshipController::class, 'projectMilestones']);
            Route::get('projects/{project}/consultations', [MentorshipController::class, 'projectConsultations']);
            Route::patch('projects/{project}/milestones/{milestone}', [MentorshipController::class, 'updateMilestone']);
            Route::patch('/projects/{project}/milestones/{milestone}/dates', [MentorshipController::class, 'updateMilestoneDates']);
        });


        Route::get('calls/{call}/milestones', [CallMilestoneController::class, 'index']);
        Route::post('calls/{call}/milestones', [CallMilestoneController::class, 'store']);
        Route::patch('calls/{call}/milestones/{milestone}', [CallMilestoneController::class, 'update']);
        Route::get('calls/{call}/milestones/{milestone}/documents', [MilestoneDocumentController::class, 'index']);
        Route::post('calls/{call}/milestones/{milestone}/documents', [MilestoneDocumentController::class, 'store']);
    });
});
