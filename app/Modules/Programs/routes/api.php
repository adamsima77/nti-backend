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


Route::middleware(['throttle:200,1'])->group(function () {
    Route::get('/calls/{id}/lang/{lang}', [CallController::class, 'fetchCallByIdAndLang'])->whereNumber('id');
    Route::get('/programs/lang/{lang}',   [ProgramsController::class, 'getProgramByLang']);
    Route::get('/calls/lang/{lang}',      [CallController::class, 'fetchCallByLang']);
    Route::get('calls',                   [CallController::class, 'index']);
    Route::get('calls/{id}',              [CallController::class, 'show'])->whereNumber('id');
    Route::get('/qualification-stacks/lang/{lang}', [QualificationStackController::class, 'fetchStacksByLang']);
});


Route::middleware(['throttle:35,1'])->group(function () {
    Route::get('calls/{id}/pdf', [ExportController::class, 'callPdf'])->name('calls.pdf')->whereNumber('id');
});


Route::middleware(['auth:sanctum', 'verified', 'throttle:20,1'])->group(function () {
    Route::get('calls/export/{format?}', [ExportController::class, 'calls'])->name('calls.export');
});


Route::middleware(['auth:sanctum', 'verified', 'throttle:200,1'])->prefix('v1')->group(function () {

    // Core Resource Write Operations
    Route::apiResource('programs', ProgramsController::class)->only(['store', 'update', 'destroy'])->names('programs');
    Route::apiResource('/program-types', TypeOfProgramController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('/call-statuses', StatusOfCallController::class)->only(['store', 'update', 'destroy']);

    // Admin Call Controls
    Route::post('admin/calls',        [CallController::class, 'store'])->name('admin.calls.store');
    Route::put('admin/calls/{id}',    [CallController::class, 'update'])->name('admin.calls.update');
    Route::delete('admin/calls/{id}', [CallController::class, 'destroy'])->name('admin.calls.destroy');

    // Call Workflow Transitions
    Route::patch('calls/{call}/workflow', [CallWorkflowController::class, 'transition']);

    // Commission Modifications
    Route::post('admin/calls/{id}/setup-commission',      [CallController::class, 'setupCommission']);
    Route::post('admin/calls/{id}/select-team',           [CallController::class, 'selectTeam']);

    // Criteria Mutators
    Route::post('admin/criteria',        [CriterionController::class, 'store']);
    Route::put('admin/criteria/{id}',    [CriterionController::class, 'update']);
    Route::delete('admin/criteria/{id}', [CriterionController::class, 'destroy']);

    // Form Schema Execution Mutators
    Route::post('admin/calls/{callId}/form-schema', [FormSchemaController::class, 'store']);
    Route::put('admin/calls/{callId}/form-schema/{id}', [FormSchemaController::class, 'update']);
    Route::post('admin/calls/{callId}/form-schema/{id}/publish', [FormSchemaController::class, 'publish']);
    Route::delete('admin/calls/{callId}/form-schema/{id}', [FormSchemaController::class, 'destroy']);

    // Form Field Execution Mutators
    Route::post('admin/calls/{callId}/form-schema/{schemaId}/fields', [FormSchemaController::class, 'storeField']);
    Route::post('admin/calls/{callId}/form-schema/{schemaId}/fields/reorder', [FormSchemaController::class, 'reorderFields']);
    Route::put('admin/calls/{callId}/form-schema/{schemaId}/fields/{fieldId}', [FormSchemaController::class, 'updateField']);
    Route::delete('admin/calls/{callId}/form-schema/{schemaId}/fields/{fieldId}', [FormSchemaController::class, 'destroyField']);
});


Route::middleware(['auth:sanctum', 'verified', 'throttle:200,1'])->group(function () {

    // Read-only actions for core setup systems
    Route::apiResource('/program-types', TypeOfProgramController::class)->only(['index', 'show']);
    Route::apiResource('/call-statuses', StatusOfCallController::class)->only(['index', 'show']);

    Route::prefix('v1')->group(function () {
        Route::apiResource('programs', ProgramsController::class)->only(['index', 'show'])->names('programs');

        // Admin Navigation Indexes
        Route::get('admin/calls',         [CallController::class, 'adminIndex'])->name('admin.calls.index');
        Route::get('admin/calls/{id}',    [CallController::class, 'adminShow'])->name('admin.calls.show');
        Route::get('calls/{call}/workflow',   [CallWorkflowController::class, 'show']);

        // Commission Lookups
        Route::get('admin/calls/{id}/commission-setup',       [CallController::class, 'commissionSetup']);
        Route::get('admin/calls/{id}/org-members',            [CallController::class, 'orgMembers']);
        Route::get('admin/calls/{id}/program-b-applications', [CallController::class, 'programBApplications']);

        // Criteria Lookups
        Route::get('admin/criteria',      [CriterionController::class, 'index']);
        Route::get('admin/criteria/{id}', [CriterionController::class, 'show']);

        // Form Schemas & Field Structural Layout reads
        Route::get('admin/calls/{callId}/form-schema', [FormSchemaController::class, 'show']);
        Route::get('admin/calls/{callId}/form-schema/{schemaId}/fields', [FormSchemaController::class, 'listFields']);
    });
});
