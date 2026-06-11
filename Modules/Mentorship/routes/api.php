<?php

use Illuminate\Support\Facades\Route;
use Modules\Mentorship\Http\Controllers\MentorshipController;
use Modules\Mentorship\Http\Controllers\CallMilestoneController;
use Modules\Mentorship\Http\Controllers\MilestoneController;
use Modules\Mentorship\Http\Controllers\MilestoneDocumentController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/mentor/projects/{project}', [MentorshipController::class, 'projectDetail']);
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
       Route::get('/call-name', [MentorshipController::class, 'fetchMentorCalls']);
       Route::get('/fetch-detail/{application}', [MentorshipController::class, 'fetchDetail']);
        Route::get   ('projects/{project}',                          [MentorshipController::class, 'projectDetail']);
        Route::put   ('projects/{project}/consultations/{session}',  [MentorshipController::class, 'updateConsultation']);
        Route::delete('projects/{project}/consultations/{session}',  [MentorshipController::class, 'deleteConsultation']);
        Route::patch('/projects/{project}/milestones/{milestone}/dates', [MentorshipController::class, 'updateMilestoneDates']);
    });

    Route::get('/mentors', [MentorshipController::class, 'fetchMentors']);

    Route::apiResource('milestones', MilestoneController::class);

    Route::get('calls/{call}/milestones', [CallMilestoneController::class, 'index']);
    Route::post('calls/{call}/milestones', [CallMilestoneController::class, 'store']);
    Route::patch('calls/{call}/milestones/{milestone}', [CallMilestoneController::class, 'update']);
    Route::delete('calls/{call}/milestones/{milestone}', [CallMilestoneController::class, 'destroy']);

    // Dokumenty k míľniku
    Route::get('calls/{call}/milestones/{milestone}/documents', [MilestoneDocumentController::class, 'index']);
    Route::post('calls/{call}/milestones/{milestone}/documents', [MilestoneDocumentController::class, 'store']);
    Route::get('calls/{call}/milestones/{milestone}/documents/{document}/download', [MilestoneDocumentController::class, 'download']);
    Route::delete('calls/{call}/milestones/{milestone}/documents/{document}', [MilestoneDocumentController::class, 'destroy']);


    Route::put('/update-milestone/{milestone}', [MilestoneController::class, 'studentAnswer']);
    Route::get('/fetch-student-milestones', [MilestoneController::class, 'fetchMilestonesForStudent']);
});
