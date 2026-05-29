<?php

namespace Modules\AuditCompliance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\AuditCompliance\Enums\GdprReportStatus;
use Modules\AuditCompliance\Jobs\ProcessGdprExport;
use Modules\AuditCompliance\Models\GdprReport;

class GdprReportController extends Controller
{
    use AuthorizesRequests;

    public function generateGdprReport(Request $request): JsonResponse
    {
        Log::info('[GDPR CONTROLLER] 1. generateGdprReport endpoint hit.');

        $this->authorize('generate', GdprReport::class);
        Log::info('[GDPR CONTROLLER] 2. Authorization passed.');

        $request->validate([
            'target_user_id' => 'required|exists:users,id',
            'format'         => 'required|in:pdf,csv,xlsx',
        ]);
        Log::info('[GDPR CONTROLLER] 3. Validation passed.', [
            'target_user_id' => $request->input('target_user_id'),
            'format' => $request->input('format')
        ]);

        $admin        = $request->user();
        $targetUserId = $request->input('target_user_id');
        $format       = $request->input('format');

        $report = GdprReport::create([
            'user_id'      => $targetUserId,
            'requested_by' => $admin->id,
            'status'       => GdprReportStatus::PENDING->value,
        ]);
        Log::info('[GDPR CONTROLLER] 4. GdprReport entry created in DB.', ['report_id' => $report->id]);

        dispatch(new ProcessGdprExport((int) $report->id, (string) $format));
        Log::info('[GDPR CONTROLLER] 5. ProcessGdprExport job dispatched to queue system.');

        return response()->json([
            'message'   => 'Report generation has been queued.',
            'report_id' => $report->id,
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * Polled by the frontend every ~3 s after queuing to know when
     * the report is ready. Only exposes status — no file data.
     */
    public function show(GdprReport $report): JsonResponse
    {
        Log::info('[GDPR CONTROLLER] [POLLING] show() called.', [
            'report_id' => $report->id,
            'current_status' => $report->status
        ]);

        $this->authorize('view', $report);

        return response()->json([
            'id'     => $report->id,
            'status' => $report->status,
        ]);
    }

    public function download(GdprReport $report)
    {
        Log::info('[GDPR CONTROLLER] [DOWNLOAD] download() initialized.', ['report_id' => $report->id]);

        $this->authorize('download', $report);

        if ($report->status === GdprReportStatus::EXPIRED->value || ! $report->attachment_id) {
            Log::warning('[GDPR CONTROLLER] [DOWNLOAD] Blocked: Report file has expired or lacks an attachment ID.', [
                'status' => $report->status,
                'attachment_id' => $report->attachment_id
            ]);
            return response()->json(
                ['message' => 'This report file has expired and was purged.'],
                Response::HTTP_GONE
            );
        }

        if ($report->status !== GdprReportStatus::COMPLETED->value) {
            Log::warning('[GDPR CONTROLLER] [DOWNLOAD] Blocked: Report generation is not marked COMPLETED.', [
                'status' => $report->status
            ]);
            return response()->json(
                ['message' => 'The report is still processing.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // attachment() → Document; the file lives on its latest DocumentVersion
        $document = $report->attachment;
        $version  = $document?->versions()->latest()->first();

        if (! $version || ! Storage::disk('local')->exists($version->file_path)) {
            Log::error('[GDPR CONTROLLER] [DOWNLOAD] CRITICAL: Database records match, but physical file missing from disk.', [
                'file_path' => $version?->file_path ?? 'N/A'
            ]);
            return response()->json(
                ['message' => 'Physical file not found on server.'],
                Response::HTTP_NOT_FOUND
            );
        }

        $report->update(['downloaded_at' => now()]);
        Log::info('[GDPR CONTROLLER] [DOWNLOAD] Success: Handing file off to downstream client browser.', [
            'file_name' => $version->file_name,
            'file_path' => $version->file_path
        ]);

        // file_name already has the correct extension (set during generation)
        return Storage::disk('local')->download($version->file_path, $version->file_name);
    }
}
