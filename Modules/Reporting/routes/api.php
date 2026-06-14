<?php

use Illuminate\Support\Facades\Route;
use Modules\Reporting\Http\Controllers\AdminDashboardController;
use Modules\Reporting\Http\Controllers\ExportController;
use Modules\Reporting\Http\Controllers\ProjectKpiController;
use Modules\Reporting\Http\Controllers\ProjectOutputController;
use Modules\Reporting\Http\Controllers\SuperAdminDashboardController;


Route::middleware(['auth:sanctum', 'verified', 'throttle:35,1'])->group(function () {
    Route::get('exports/{exportRequest}', [ExportController::class, 'showExportRequest'])->name('exports.show');
    Route::get('exports/{exportRequest}/download', [ExportController::class, 'downloadExportRequest'])->name('exports.download');
    Route::get('v1/admin/calls/{callId}/closure-report', [ExportController::class, 'callClosureReport'])->name('api.calls.closure-report');
    Route::get('v1/admin/calls/{callId}/report/{format?}', [ExportController::class, 'callReport'])->name('api.calls.report');
    Route::get('evaluations/export/{format?}', [ExportController::class, 'evaluations'])->name('evaluations.export');
});


Route::middleware(['auth:sanctum', 'verified', 'throttle:80,1'])->group(function () {

    // KPI Data Modifications
    Route::post('applications/{applicationId}/kpis', [ProjectKpiController::class, 'store'])->name('kpis.store');
    Route::patch('kpis/{id}', [ProjectKpiController::class, 'update'])->name('kpis.update');
    Route::delete('kpis/{id}', [ProjectKpiController::class, 'destroy'])->name('kpis.destroy');

    // Project Output & Document Management Actions
    Route::post('applications/{applicationId}/outputs', [ProjectOutputController::class, 'store'])->name('outputs.store');
    Route::patch('outputs/{id}', [ProjectOutputController::class, 'update'])->name('outputs.update');
    Route::delete('outputs/{id}', [ProjectOutputController::class, 'destroy'])->name('outputs.destroy');
    Route::post('outputs/{id}/mark-as-delivered', [ProjectOutputController::class, 'markAsDelivered'])->name('outputs.mark-as-delivered');
    Route::post('outputs/{id}/attach-documents', [ProjectOutputController::class, 'attachDocuments'])->name('outputs.attach-documents');
    Route::post('outputs/{id}/detach-documents', [ProjectOutputController::class, 'detachDocuments'])->name('outputs.detach-documents');
});


Route::middleware(['auth:sanctum', 'verified', 'throttle:500,1'])->group(function () {
    Route::get('/security-alerts', [SuperAdminDashboardController::class, 'securityAlertsNewer']);
    Route::get('/active-problems', [SuperAdminDashboardController::class, 'activeSystemProblemsCount']);
    Route::get('/gdpr-prune', [SuperAdminDashboardController::class, 'fetchGdprPrune']);
    Route::get('/status-of-services', [SuperAdminDashboardController::class, 'fetchStatusOfServices']);
    Route::get('/logs', [SuperAdminDashboardController::class, 'fetchLogs']);
    Route::get('/fetch-all-logs', [SuperAdminDashboardController::class, 'fetchAllLogs']);
});


Route::middleware(['auth:sanctum', 'verified', 'throttle:230,1'])->group(function () {

    // General Management Visual Aggregations
    Route::get('/admin/application-count', [AdminDashboardController::class, 'fetchApplicationsCount']);
    Route::get('/admin/fetch-active-calls-count', [AdminDashboardController::class, 'fetchActiveCallsCount']);
    Route::get('/admin/fetch-user-count', [AdminDashboardController::class, 'fetchUsersCount']);
    Route::get('/admin/fetch-team-count', [AdminDashboardController::class, 'fetchTeamCount']);
    Route::get('/admin/fetch-active-calls', [AdminDashboardController::class, 'fetchActiveCalls']);
    Route::get('/admin/fetch-pending-approval', [AdminDashboardController::class, 'fetchPendingApprovalOrganizations']);

    // KPI & Output Lookups
    Route::get('applications/{applicationId}/kpis', [ProjectKpiController::class, 'index'])->name('kpis.index');
    Route::get('applications/{applicationId}/kpis/statistics', [ProjectKpiController::class, 'statistics'])->name('kpis.statistics');
    Route::get('kpis/{id}', [ProjectKpiController::class, 'show'])->name('kpis.show');

    Route::get('applications/{applicationId}/outputs', [ProjectOutputController::class, 'index'])->name('outputs.index');
    Route::get('applications/{applicationId}/outputs/statistics', [ProjectOutputController::class, 'statistics'])->name('outputs.statistics');
    Route::get('outputs/{id}', [ProjectOutputController::class, 'show'])->name('outputs.show');

    // High-Level Super Admin Metric Lookups
    Route::get('/users-count', [SuperAdminDashboardController::class, 'fetchAllUsersCount']);
    Route::get('/organizations-count', [SuperAdminDashboardController::class, 'fetchAllOrganizationsCount']);
});
