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


        $this->authorize('generate', GdprReport::class);


        $request->validate([
            'target_user_id' => 'required|exists:users,id',
            'format'         => 'required|in:pdf,csv,xlsx',
        ]);


        $admin        = $request->user();
        $targetUserId = $request->input('target_user_id');
        $format       = $request->input('format');

        $report = GdprReport::create([
            'user_id'      => $targetUserId,
            'requested_by' => $admin->id,
            'status'       => GdprReportStatus::PENDING->value,
        ]);


        dispatch(new ProcessGdprExport((int) $report->id, (string) $format));


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


        $this->authorize('view', $report);

        return response()->json([
            'id'     => $report->id,
            'status' => $report->status,
        ]);
    }

    public function download(GdprReport $report)
    {


        $this->authorize('download', $report);

        if ($report->status === GdprReportStatus::EXPIRED->value || ! $report->attachment_id) {

            return response()->json(
                ['message' => 'This report file has expired and was purged.'],
                Response::HTTP_GONE
            );
        }

        if ($report->status !== GdprReportStatus::COMPLETED->value) {

            return response()->json(
                ['message' => 'The report is still processing.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // attachment() → Document; the file lives on its latest DocumentVersion
        $document = $report->attachment;
        $version  = $document?->versions()->latest()->first();

        if (! $version || ! Storage::disk('local')->exists($version->file_path)) {

            return response()->json(
                ['message' => 'Physical file not found on server.'],
                Response::HTTP_NOT_FOUND
            );
        }

        $report->update(['downloaded_at' => now()]);

        // file_name already has the correct extension (set during generation)
        return Storage::disk('local')->download($version->file_path, $version->file_name);
    }
}
