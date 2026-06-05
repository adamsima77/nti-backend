<?php

use Illuminate\Support\Facades\Route;
use Modules\Reporting\Http\Controllers\AdminDashboardController;
use Modules\Reporting\Http\Controllers\ExportController;
use Modules\Reporting\Http\Controllers\ProjectKpiController;
use Modules\Reporting\Http\Controllers\ProjectOutputController;
use Modules\Reporting\Http\Controllers\SuperAdminDashboardController;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/admin/application-count', [AdminDashboardController::class, 'fetchApplicationsCount']);
    Route::get('/admin/fetch-active-calls-count', [AdminDashboardController::class, 'fetchActiveCallsCount']);
    Route::get('/admin/fetch-user-count', [AdminDashboardController::class, 'fetchUsersCount']);
    Route::get('/admin/fetch-team-count', [AdminDashboardController::class, 'fetchTeamCount']);
    Route::get('/admin/fetch-active-calls', [AdminDashboardController::class, 'fetchActiveCalls']);
    Route::get('/admin/fetch-pending-approval', [AdminDashboardController::class, 'fetchPendingApprovalOrganizations']);

    Route::get('exports/{exportRequest}', [ExportController::class, 'showExportRequest'])->name('exports.show');
    Route::get('exports/{exportRequest}/download', [ExportController::class, 'downloadExportRequest'])->name('exports.download');

    // ProjectKpi routes
    Route::prefix('applications/{applicationId}/kpis')->group(function () {
        Route::get('/', [ProjectKpiController::class, 'index'])->name('kpis.index');
        Route::get('/statistics', [ProjectKpiController::class, 'statistics'])->name('kpis.statistics');
        Route::post('/', [ProjectKpiController::class, 'store'])->name('kpis.store');
    });

    Route::prefix('kpis')->group(function () {
        Route::get('{id}', [ProjectKpiController::class, 'show'])->name('kpis.show');
        Route::patch('{id}', [ProjectKpiController::class, 'update'])->name('kpis.update');
        Route::delete('{id}', [ProjectKpiController::class, 'destroy'])->name('kpis.destroy');
    });

    // ProjectOutput routes
    Route::prefix('applications/{applicationId}/outputs')->group(function () {
        Route::get('/', [ProjectOutputController::class, 'index'])->name('outputs.index');
        Route::get('/statistics', [ProjectOutputController::class, 'statistics'])->name('outputs.statistics');
        Route::post('/', [ProjectOutputController::class, 'store'])->name('outputs.store');
    });

    Route::prefix('outputs')->group(function () {
        Route::get('{id}', [ProjectOutputController::class, 'show'])->name('outputs.show');
        Route::patch('{id}', [ProjectOutputController::class, 'update'])->name('outputs.update');
        Route::delete('{id}', [ProjectOutputController::class, 'destroy'])->name('outputs.destroy');
        Route::post('{id}/mark-as-delivered', [ProjectOutputController::class, 'markAsDelivered'])->name('outputs.mark-as-delivered');
        Route::post('{id}/attach-documents', [ProjectOutputController::class, 'attachDocuments'])->name('outputs.attach-documents');
        Route::post('{id}/detach-documents', [ProjectOutputController::class, 'detachDocuments'])->name('outputs.detach-documents');
    });


        Route::get('/users-count', [SuperAdminDashboardController::class, 'fetchAllUsersCount']);
        Route::get('/organizations-count', [SuperAdminDashboardController::class, 'fetchAllOrganizationsCount']);
        Route::get('/security-alerts', [SuperAdminDashboardController::class, 'securityAlertsNewer']);
        Route::get('/active-problems', [SuperAdminDashboardController::class, 'activeSystemProblemsCount']);
        Route::get('/gdpr-prune', [SuperAdminDashboardController::class, 'fetchGdprPrune']);
        Route::get('/status-of-services', [SuperAdminDashboardController::class, 'fetchStatusOfServices']);
        Route::get('/logs', [SuperAdminDashboardController::class, 'fetchLogs']);
        Route::get('/fetch-all-logs', [SuperAdminDashboardController::class, 'fetchAllLogs']);
});
