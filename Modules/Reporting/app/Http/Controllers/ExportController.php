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
use Modules\Evaluation\Models\Evaluation;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Models\Call;
use Modules\Reporting\Exports\ApplicationExport;
use Modules\Reporting\Exports\CallExport;
use Modules\Reporting\Exports\EvaluationExport;
use Modules\Reporting\Exports\UserExport;
use Modules\Reporting\Models\ExportRequest;
use Modules\Reporting\Support\CallReportLabels;
use Modules\Teams\Models\Team;

class ExportController extends Controller
{
    use AuthorizesRequests;

    public function applications(
        Request $request,
        string $format = 'xlsx',
        QueuedExportService $queuedExportService,
        PdfService $pdfService,
    ){
        $this->authorize('export', Applications::class);

        $format = strtolower($format ?: 'xlsx');

        $filters = [
            'call_id'        => $request->input('call_id'),
            'active_status'      => $request->input('status_id'),
            'submitted_from' => $request->input('submitted_from'),
            'submitted_to'   => $request->input('submitted_to'),
            'search' => $request->input('search')
        ];

        // ── PDF ────────────────────────────────────────────────────────────────
        if ($format === 'pdf') {
            $fileName     = 'applications.pdf';
            $applications = $this->buildApplicationExportQuery($filters)->get();

            $serialised = json_decode(json_encode(
                $applications->map(fn ($a) => $a->toArray())->all()
            ));

            $viewData = [
                'applications' => $serialised,
                'filters'      => $filters,
                'generatedAt'  => now()->format('d.m.Y H:i'),
            ];

            if ($request->boolean('async')) {
                return $this->queuePdfViewResponse(
                    $request,
                    $queuedExportService,
                    'applications',
                    $fileName,
                    'reporting::applications_export',
                    $viewData,
                );
            }

            return $pdfService->download('reporting::applications_export', $viewData, $fileName);
        }

        // ── XLSX / CSV ─────────────────────────────────────────────────────────
        $fileName   = 'applications.' . $format;
        $writerType = $format === 'csv'
            ? \Maatwebsite\Excel\Excel::CSV
            : \Maatwebsite\Excel\Excel::XLSX;

        if ($request->boolean('async')) {
            return $this->queueExcelResponse(
                $request,
                $queuedExportService,
                'applications',
                $fileName,
                ApplicationExport::class,
                $writerType,
                [$filters],
            );
        }

        return Excel::download(new ApplicationExport($filters), $fileName, $writerType);
    }


private function buildApplicationExportQuery(array $filters)
{
    $query = Application::query()
        ->with([
            'team',
            'call',
            'status',
            'mentorships.mentor',
            'creator',
        ]);

    if (! empty($filters['call_id'])) {
        $query->where('call_id', $filters['call_id']);
    }

    if (! empty($filters['active_status'])) {
        $query->where('active_status', $filters['active_status']);
    }

    if (! empty($filters['submitted_from'])) {
        $query->whereDate('submitted_at', '>=', $filters['submitted_from']);
    }

    if (! empty($filters['submitted_to'])) {
        $query->whereDate('submitted_at', '<=', $filters['submitted_to']);
    }

    if (! empty($filters['search'])) {
        $term = '%' . $filters['search'] . '%';

        $query->where(function ($subQuery) use ($term) {
            $subQuery->where('reference', 'ilike', $term)
                ->orWhereHas('team', fn ($q) => $q->where('name', 'ilike', $term));
        });
    }

    return $query->orderByDesc('submitted_at');
}

    public function evaluations(Request $request, string $format = 'xlsx', QueuedExportService $queuedExportService)
    {
        $this->authorize('viewAny', Evaluation::class);

        $format = strtolower($format ?: 'xlsx');
        $fileName = 'evaluations.' . $format;
        $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;
        $filters = [
            'call_id' => $request->input('call_id'),
        ];

        if ($request->boolean('async')) {
            return $this->queueExcelResponse(
                $request,
                $queuedExportService,
                'evaluations',
                $fileName,
                EvaluationExport::class,
                $writerType,
                [$filters]
            );
        }

        return Excel::download(new EvaluationExport($filters), $fileName, $writerType);
    }

    public function users(Request $request, string $format = 'xlsx', QueuedExportService $queuedExportService, PdfService $pdfService)
    {
        $this->authorize('export', User::class);

        $format = strtolower($format ?: 'xlsx');
        $fileName = 'users.' . $format;
        $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        $filters = [
            'search' => $request->input('search'),
            'role'   => $request->input('role'),
            'status' => $request->input('status'),
        ];

        if ($format === 'pdf') {
            $query = $this->buildUserExportQuery($filters);
            $users = $query->get();

            if ($request->boolean('async')) {
                return $this->queuePdfViewResponse(
                    $request,
                    $queuedExportService,
                    'users',
                    $fileName,
                    'reporting::users_export',
                    ['users' => $users->map(fn ($user) => (object) $user->toArray())->all()]
                );
            }

            return $pdfService->download('reporting::users_export', ['users' => $users], $fileName);
        }

        if ($request->boolean('async')) {
            return $this->queueExcelResponse(
                $request,
                $queuedExportService,
                'users',
                $fileName,
                UserExport::class,
                $writerType,
                [$filters]
            );
        }

        return Excel::download(new UserExport($filters), $fileName, $writerType);
    }

    public function calls(Request $request, string $format = 'xlsx', QueuedExportService $queuedExportService, PdfService $pdfService)
    {
        $this->authorize('export', Call::class);

        $format = strtolower($format ?: 'xlsx');
        $filters = [
            'status'        => $request->input('status'),
            'deadline_from' => $request->input('deadline_from'),
            'deadline_to'   => $request->input('deadline_to'),
        ];

        if ($format === 'pdf') {
            $fileName = 'calls.pdf';
            $query = $this->buildCallExportQuery($filters);
            $calls = $query->get();

            if ($request->boolean('async')) {
                return $this->queuePdfViewResponse(
                    $request,
                    $queuedExportService,
                    'calls',
                    $fileName,
                    'reporting::calls_export',
                    ['calls' => $calls->map(fn ($call) => (object) $call->toArray())->all()]
                );
            }

            return $pdfService->download('reporting::calls_export', ['calls' => $calls], $fileName);
        }

        $fileName = 'calls.' . $format;
        $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        if ($request->boolean('async')) {
            return $this->queueExcelResponse(
                $request,
                $queuedExportService,
                'calls',
                $fileName,
                CallExport::class,
                $writerType,
                [$filters]
            );
        }

        return Excel::download(new CallExport($filters), $fileName, $writerType);
    }

    protected function buildCallExportQuery(array $filters)
    {
        $query = Call::query();

        if (! empty($filters['status'])) {
            $mainTable = $query->getModel()->getTable();

            $query->whereHas('statusHistory', function ($q) use ($filters, $mainTable) {
                $q->whereHas('status', function ($statusQuery) use ($filters) {
                    $statusQuery->where('name', $filters['status']);
                })
                    ->where('id', function ($subQuery) use ($mainTable) {
                        $subQuery->select('id')
                            ->from('status_of_call_has_call')
                            ->whereColumn('call_id', $mainTable . '.id')
                            ->latest()
                            ->limit(1);
                    });
            });
        }

        if (! empty($filters['deadline_from'])) {
            $query->whereDate('application_deadline', '>=', $filters['deadline_from']);
        }

        if (! empty($filters['deadline_to'])) {
            $query->whereDate('application_deadline', '<=', $filters['deadline_to']);
        }

        return $query;
    }

    protected function buildUserExportQuery(array $filters)
    {
        $query = User::query()
            ->with('status')
            ->where('status_id', '!=', UserStatus::ANONYMIZED->value)
            ->orderByDesc('created_at');

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('surname', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (! empty($filters['role'])) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $filters['role']));
        }

        if (! empty($filters['status'])) {
            $query->where('status_id', $filters['status']);
        }

        return $query;
    }

    protected function queuePdfViewResponse(
        Request $request,
        QueuedExportService $queuedExportService,
        string $exportKey,
        string $fileName,
        string $view,
        array $viewData,
        array $options = []
    ): JsonResponse {
        $exportRequest = $queuedExportService->queue(
            (int) $request->user()->id,
            $exportKey,
            'pdf',
            'pdf',
            $fileName,
            [
                'view' => $view,
                'view_data' => $viewData,
                'options' => $options,
            ]
        );

        return $this->queuedExportResponse($request, $exportRequest);
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

    public function callPdf($id, Request $request, PdfService $pdfService, QueuedExportService $queuedExportService)
    {
        $call = Call::query()
            ->with([
                'program.typeOfProgram:id,name',
                'organization:id,name',
                'currentStatusHistory.status:id,name',
                'callCriteria.criterionTranslations',
            ])
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
                    'program.typeOfProgram:id,name',
                    'organization:id,name',
                    'currentStatusHistory.status:id,name',
                    'callCriteria.criterionTranslations',
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
        string $writerType,
        ?array $exportArgs = null
    ): JsonResponse {
        $meta = [
            'export_class' => $exportClass,
            'writer_type' => $writerType,
        ];

        if ($exportArgs !== null) {
            $meta['export_args'] = $exportArgs;
        }

        $exportRequest = $queuedExportService->queue(
            (int) $request->user()->id,
            $exportKey,
            'excel',
            pathinfo($fileName, PATHINFO_EXTENSION),
            $fileName,
            $meta
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
        string $dataKey,
        array $extraData = []
    ): JsonResponse {
        $exportRequest = $queuedExportService->queue(
            (int) $request->user()->id,
            $exportKey,
            'pdf',
            'pdf',
            $fileName,
            [
                'model_class' => $modelClass,
                'model_id'    => $modelId,
                'relations'   => $relations,
                'view'        => $view,
                'data_key'    => $dataKey,
                'extra_data'  => $extraData,
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

    public function callReport(Request $request, int $callId, string $format = 'pdf', QueuedExportService $queuedExportService, PdfService $pdfService)
    {
        $lang = in_array($request->query('lang'), ['sk', 'en']) ? $request->query('lang') : 'sk';

        $relations = CallReportLabels::relations();

        $call = Call::with($relations)->findOrFail($callId);

        if ($format === 'pdf') {
            $fileName = "project-report-{$callId}-{$lang}.pdf";

            if ($request->boolean('async')) {
                return $this->queuePdfResponse(
                    $request,
                    $queuedExportService,
                    'call_pdf',
                    $fileName,
                    Call::class,
                    $callId,
                    $relations,
                    'programs::pdf.project-report',
                    'call',
                    ['lang' => $lang]
                );
            }

            return $pdfService->download('programs::pdf.project-report', ['call' => $call, 'lang' => $lang], $fileName);
        }

        $fileName = "project-report-{$callId}-{$lang}.xlsx";

        if ($request->boolean('async')) {
            return $this->queueExcelResponse(
                $request,
                $queuedExportService,
                'call_report',
                $fileName,
                CallExport::class,
                \Maatwebsite\Excel\Excel::XLSX,
                [['call_id' => $callId, 'lang' => $lang]]
            );
        }

        return Excel::download(new CallExport(['call_id' => $callId, 'lang' => $lang]), $fileName, \Maatwebsite\Excel\Excel::XLSX);
    }

    public function callClosureReport(Request $request, int $callId): JsonResponse
    {
        $exportRequest = ExportRequest::where('export_key', 'call_pdf')
            ->where('file_name', "project-report-{$callId}.pdf")
            ->latest()
            ->first();

        if (!$exportRequest) {
            return response()->json(['message' => 'Report pre tento call neexistuje.'], 404);
        }

        return response()->json([
            'export_request' => $this->formatExportRequest($request, $exportRequest),
        ]);
    }

    protected function authorizeExportRequest(Request $request, ExportRequest $exportRequest): void
    {
        if ((int) $exportRequest->user_id !== (int) $request->user()->id) {
            abort(403);
        }
    }
}
