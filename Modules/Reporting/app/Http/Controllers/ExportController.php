<?php

namespace Modules\Reporting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Exports\QueuedExportService;
use App\Services\Pdf\PdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\Applications;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Models\Call;
use Modules\Reporting\Exports\ApplicationExport;
use Modules\Reporting\Exports\CallExport;
use Modules\Reporting\Exports\UserExport;
use Modules\Reporting\Models\ExportRequest;
use Modules\Teams\Models\Team;

class ExportController extends Controller
{
    use AuthorizesRequests;

    public function applications(Request $request, string $format = 'xlsx', QueuedExportService $queuedExportService)
    {
        $this->authorize('export', Applications::class);

        $format = strtolower($format ?: 'xlsx');
        $fileName = 'applications.' . $format;
        $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        if ($request->boolean('async')) {
            return $this->queueExcelResponse(
                $request,
                $queuedExportService,
                'applications',
                $fileName,
                ApplicationExport::class,
                $writerType
            );
        }

        return Excel::download(new ApplicationExport(), $fileName, $writerType);
    }

    public function users(Request $request, string $format = 'xlsx', QueuedExportService $queuedExportService)
    {
        $this->authorize('export', User::class);

        $format = strtolower($format ?: 'xlsx');
        $fileName = 'users.' . $format;
        $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        if ($request->boolean('async')) {
            return $this->queueExcelResponse(
                $request,
                $queuedExportService,
                'users',
                $fileName,
                UserExport::class,
                $writerType
            );
        }

        return Excel::download(new UserExport(), $fileName, $writerType);
    }

    public function calls(Request $request, string $format = 'xlsx', QueuedExportService $queuedExportService)
    {
        $this->authorize('export', Call::class);

        $format = strtolower($format ?: 'xlsx');
        $fileName = 'calls.' . $format;
        $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        if ($request->boolean('async')) {
            return $this->queueExcelResponse(
                $request,
                $queuedExportService,
                'calls',
                $fileName,
                CallExport::class,
                $writerType
            );
        }

        return Excel::download(new CallExport(), $fileName, $writerType);
    }

    public function applicationPdf(Request $request, int $id, PdfService $pdfService, QueuedExportService $queuedExportService)
    {
        $application = Applications::query()
            ->with([
                'call:id,name',
                'status:id,name',
                'documents:id',
                'statusHistory.status:id,name',
            ])
            ->where('created_by', $request->user()->id)
            ->findOrFail($id);

        if ($request->boolean('async')) {
            return $this->queuePdfResponse(
                $request,
                $queuedExportService,
                'application_pdf',
                'application-' . $application->id . '.pdf',
                Applications::class,
                $application->id,
                [
                    'call:id,name',
                    'status:id,name',
                    'documents:id',
                    'statusHistory.status:id,name',
                ],
                'applications::pdf.application-details',
                'application'
            );
        }

        return $pdfService->download(
            'applications::pdf.application-details',
            ['application' => $application],
            'application-' . $application->id . '.pdf'
        );
    }

    public function userPdf(User $user, Request $request, PdfService $pdfService, QueuedExportService $queuedExportService)
    {
        $this->authorize('pdf', $user);

        $user->load(['status', 'roles', 'teams']);

        if ($request->boolean('async')) {
            return $this->queuePdfResponse(
                $request,
                $queuedExportService,
                'user_pdf',
                'user-profile-' . $user->id . '.pdf',
                User::class,
                $user->id,
                ['status', 'roles', 'teams'],
                'identityaccess::pdf.profile',
                'user'
            );
        }

        return $pdfService->download(
            'identityaccess::pdf.profile',
            ['user' => $user],
            'user-profile-' . $user->id . '.pdf'
        );
    }

    public function callPdf(int $id, Request $request, PdfService $pdfService, QueuedExportService $queuedExportService)
    {
        $call = Call::query()
            ->with([
                'program:id,name',
                'organization:id,name',
                'currentStatusHistory.status:id,name',
                'callCriteria:id,name',
            ])
            ->whereHas('currentStatusHistory.status', function ($query) {
                $query->where('name', 'Publikované');
            })
            ->findOrFail($id);

        if ($request->boolean('async')) {
            return $this->queuePdfResponse(
                $request,
                $queuedExportService,
                'call_pdf',
                'project-report-' . $call->id . '.pdf',
                Call::class,
                $call->id,
                [
                    'program:id,name',
                    'organization:id,name',
                    'currentStatusHistory.status:id,name',
                    'callCriteria:id,name',
                ],
                'programs::pdf.project-report',
                'call'
            );
        }

        return $pdfService->download(
            'programs::pdf.project-report',
            ['call' => $call],
            'project-report-' . $call->id . '.pdf'
        );
    }

    public function teamPdf(Team $team, Request $request, PdfService $pdfService, QueuedExportService $queuedExportService)
    {
        $this->authorize('pdf', $team);

        $team->load('members');

        if ($request->boolean('async')) {
            return $this->queuePdfResponse(
                $request,
                $queuedExportService,
                'team_pdf',
                'team-report-' . $team->id . '.pdf',
                Team::class,
                $team->id,
                ['members'],
                'teams::pdf.team-report',
                'team'
            );
        }

        return $pdfService->download(
            'teams::pdf.team-report',
            ['team' => $team],
            'team-report-' . $team->id . '.pdf'
        );
    }

    public function showExportRequest(Request $request, ExportRequest $exportRequest): JsonResponse
    {
        $this->authorizeExportRequest($request, $exportRequest);

        return response()->json([
            'export_request' => $this->formatExportRequest($request, $exportRequest),
        ]);
    }

    public function downloadExportRequest(Request $request, ExportRequest $exportRequest)
    {
        $this->authorizeExportRequest($request, $exportRequest);

        if ($exportRequest->status !== 'completed' || $exportRequest->storage_path === null) {
            abort(409, 'Export ešte nie je pripravený.');
        }

        return Storage::disk($exportRequest->storage_disk)->download($exportRequest->storage_path, $exportRequest->file_name);
    }

    protected function queueExcelResponse(
        Request $request,
        QueuedExportService $queuedExportService,
        string $exportKey,
        string $fileName,
        string $exportClass,
        string $writerType
    ): JsonResponse {
        $exportRequest = $queuedExportService->queue(
            (int) $request->user()->id,
            $exportKey,
            'excel',
            pathinfo($fileName, PATHINFO_EXTENSION),
            $fileName,
            [
                'export_class' => $exportClass,
                'writer_type' => $writerType,
            ]
        );

        return $this->queuedExportResponse($request, $exportRequest);
    }

    protected function queuePdfResponse(
        Request $request,
        QueuedExportService $queuedExportService,
        string $exportKey,
        string $fileName,
        string $modelClass,
        int $modelId,
        array $relations,
        string $view,
        string $dataKey
    ): JsonResponse {
        $exportRequest = $queuedExportService->queue(
            (int) $request->user()->id,
            $exportKey,
            'pdf',
            'pdf',
            $fileName,
            [
                'model_class' => $modelClass,
                'model_id' => $modelId,
                'relations' => $relations,
                'view' => $view,
                'data_key' => $dataKey,
            ]
        );

        return $this->queuedExportResponse($request, $exportRequest);
    }

    protected function queuedExportResponse(Request $request, ExportRequest $exportRequest): JsonResponse
    {
        return response()->json([
            'message' => 'Generovanie exportu bolo zaradené do fronty.',
            'export_request' => $this->formatExportRequest($request, $exportRequest),
        ], 202);
    }

    protected function formatExportRequest(Request $request, ExportRequest $exportRequest): array
    {
        return [
            'id' => $exportRequest->id,
            'export_key' => $exportRequest->export_key,
            'kind' => $exportRequest->kind,
            'format' => $exportRequest->format,
            'status' => $exportRequest->status,
            'file_name' => $exportRequest->file_name,
            'error_message' => $exportRequest->error_message,
            'queued_at' => $exportRequest->queued_at?->toISOString(),
            'processed_at' => $exportRequest->processed_at?->toISOString(),
            'completed_at' => $exportRequest->completed_at?->toISOString(),
            'failed_at' => $exportRequest->failed_at?->toISOString(),
            'status_url' => route('api.exports.show', ['exportRequest' => $exportRequest]),
            'download_url' => route('api.exports.download', ['exportRequest' => $exportRequest]),
        ];
    }

    protected function authorizeExportRequest(Request $request, ExportRequest $exportRequest): void
    {
        if ((int) $exportRequest->user_id !== (int) $request->user()->id) {
            abort(403);
        }
    }
}
