<?php

use Illuminate\Support\Facades\Route;
use Modules\Programs\Http\Controllers\CallController;
use Modules\Programs\Http\Controllers\CallWorkflowController;
use Modules\Programs\Http\Controllers\CriterionController;
use Modules\Programs\Http\Controllers\FormSchemaController;
use Modules\Programs\Http\Controllers\ProgramsController;
use Modules\Programs\Http\Controllers\QualificationStackController;
use Modules\Programs\Http\Controllers\StatusOfCallController;
use Modules\Programs\Http\Controllers\TypeOfProgramController;
use Modules\Reporting\Http\Controllers\ExportController;

// ── Public routes ──────────────────────────────────────────────────────
Route::get('/calls/{id}/lang/{lang}', [CallController::class, 'fetchCallByIdAndLang'])->whereNumber('id');
Route::get('/programs/lang/{lang}',   [ProgramsController::class, 'getProgramByLang']);
Route::get('/calls/lang/{lang}',      [CallController::class, 'fetchCallByLang']);
Route::get('calls',                   [CallController::class, 'index']);
Route::get('calls/{id}',              [CallController::class, 'show'])->whereNumber('id');
Route::get('calls/{id}/pdf',          [ExportController::class, 'callPdf'])->name('calls.pdf')->whereNumber('id');
Route::get('/qualification-stacks/lang/{lang}', [QualificationStackController::class, 'fetchStacksByLang']);
// ── Authenticated routes (Sanctum protected) ───────────────────────────
Route::middleware(['auth:sanctum'])->group(function () {

    Route::apiResource('/program-types', TypeOfProgramController::class);
    Route::apiResource('/call-statuses', StatusOfCallController::class);
    Route::get('calls/export/{format?}', [ExportController::class, 'calls'])->name('calls.export');

    Route::prefix('v1')->group(function () {

        // Programs
        Route::apiResource('programs', ProgramsController::class)->names('programs');

        // ── Admin Calls ────────────────────────────────────────────────
        Route::get('admin/calls',         [CallController::class, 'adminIndex'])->name('admin.calls.index');
        Route::post('admin/calls',        [CallController::class, 'store'])->name('admin.calls.store');
        Route::get('admin/calls/{id}',    [CallController::class, 'adminShow'])->name('admin.calls.show');
        Route::put('admin/calls/{id}',    [CallController::class, 'update'])->name('admin.calls.update');
        Route::delete('admin/calls/{id}', [CallController::class, 'destroy'])->name('admin.calls.destroy');

        // Workflow
        Route::get('calls/{call}/workflow',   [CallWorkflowController::class, 'show']);
        Route::patch('calls/{call}/workflow', [CallWorkflowController::class, 'transition']);

        // Commission setup
        Route::get('admin/calls/{id}/commission-setup',       [CallController::class, 'commissionSetup']);
        Route::get('admin/calls/{id}/org-members',            [CallController::class, 'orgMembers']);
        Route::post('admin/calls/{id}/setup-commission',      [CallController::class, 'setupCommission']);
        Route::get('admin/calls/{id}/program-b-applications', [CallController::class, 'programBApplications']);
        Route::post('admin/calls/{id}/select-team',           [CallController::class, 'selectTeam']);

        // Criteria CRUD
        Route::get('admin/criteria',         [CriterionController::class, 'index']);
        Route::post('admin/criteria',        [CriterionController::class, 'store']);
        Route::get('admin/criteria/{id}',    [CriterionController::class, 'show']);
        Route::put('admin/criteria/{id}',    [CriterionController::class, 'update']);
        Route::delete('admin/criteria/{id}', [CriterionController::class, 'destroy']);

        // Form schema per call
        Route::get('admin/calls/{callId}/form-schema',
            [FormSchemaController::class, 'show']);

        Route::post('admin/calls/{callId}/form-schema',
            [FormSchemaController::class, 'store']);

        Route::put('admin/calls/{callId}/form-schema/{id}',
            [FormSchemaController::class, 'update']);

        Route::post('admin/calls/{callId}/form-schema/{id}/publish',
            [FormSchemaController::class, 'publish']);

        Route::delete('admin/calls/{callId}/form-schema/{id}',
            [FormSchemaController::class, 'destroy']);

        // Form fields
        Route::get('admin/calls/{callId}/form-schema/{schemaId}/fields',
            [FormSchemaController::class, 'listFields']);

        Route::post('admin/calls/{callId}/form-schema/{schemaId}/fields',
            [FormSchemaController::class, 'storeField']);

        Route::post('admin/calls/{callId}/form-schema/{schemaId}/fields/reorder',
            [FormSchemaController::class, 'reorderFields']);

        Route::put('admin/calls/{callId}/form-schema/{schemaId}/fields/{fieldId}',
            [FormSchemaController::class, 'updateField']);

        Route::delete('admin/calls/{callId}/form-schema/{schemaId}/fields/{fieldId}',
            [FormSchemaController::class, 'destroyField']);
    });
});
