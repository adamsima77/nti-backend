<?php

namespace Modules\Applications\Http\Controllers;

use App\Services\Exports\QueuedExportService;
use App\Http\Controllers\Controller;
use App\Services\ApplicationWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\Pdf\PdfService;
use Modules\Applications\Http\Resources\ApplicationResource;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\ApplicationAnswer;
use Modules\Applications\Models\Applications;
use Modules\Applications\Models\StatusOfApplication;
use Modules\Applications\Models\TypeOfApplication;
use Modules\Content\Models\Language;
use Modules\Programs\Models\Call;
use Modules\Programs\Models\FormSchema;
use Modules\Programs\Support\CallFormSchema;
use Modules\Teams\Models\Team;

class ApplicationController extends Controller
{
    use AuthorizesRequests;

    private function workflowService(): ApplicationWorkflowService
    {
        return app(ApplicationWorkflowService::class);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Applications::class);

        $perPage = min(max((int) $request->query('per_page', 50), 1), 100);

        $applications = Application::query()
            ->with([
                'call:id,name',
                'status:id,name',
                'team:id,name',
                'team.members.student.academicFlags',
                'documents:id',
            ])
            ->where('created_by', $request->user()->id)
            ->when(
                $request->filled('call_id'),
                fn (Builder $query) => $query->where('call_id', (int) $request->query('call_id'))
            )
            ->when(
                $request->filled('status_id'),
                fn (Builder $query) => $query->where('active_status', (int) $request->query('status_id'))
            )
            ->latest('id')
            ->paginate($perPage);

        return ApplicationResource::collection($applications);
    }

    public function show(Request $request, int $id): ApplicationResource
    {
        $application = Application::query()
            ->with([
                'call:id,name',
                'status:id,name',
                'team:id,name',
                'team.members.student.academicFlags',
                'documents:id',
                'documents.versions',
                'statusHistory.status:id,name',
                'milestones',
            ])
            ->findOrFail($id);

        $this->authorize('view', $application);

        return new ApplicationResource($application);
    }

    public function downloadPdf(Request $request, int $id, PdfService $pdfService, QueuedExportService $queuedExportService)
    {
        $application = Application::query()
            ->with([
                'call:id,name',
                'status:id,name',
                'team:id,name',
                'team.members.student.academicFlags',
                'documents:id',
                'statusHistory.status:id,name',
            ])
            ->findOrFail($id);

        $this->authorize('view', $application);

        if ($request->boolean('async')) {
            $exportRequest = $queuedExportService->queue(
                (int) $request->user()->id,
                'application_pdf',
                'pdf',
                'pdf',
                'application-' . $application->id . '.pdf',
                [
                    'model_class' => Application::class,
                    'model_id' => $application->id,
                    'relations' => [
                        'call:id,name',
                        'status:id,name',
                        'team:id,name',
                        'team.members.student.academicFlags',
                        'documents:id',
                        'statusHistory.status:id,name',
                    ],
                    'view' => 'applications::pdf.application-details',
                    'data_key' => 'application',
                ]
            );

            return response()->json([
                'message' => 'Generovanie exportu bolo zaradené do fronty.',
                'export_request' => [
                    'id' => $exportRequest->id,
                    'export_key' => $exportRequest->export_key,
                    'kind' => $exportRequest->kind,
                    'format' => $exportRequest->format,
                    'status' => $exportRequest->status,
                    'file_name' => $exportRequest->file_name,
                    'status_url' => route('api.exports.show', ['exportRequest' => $exportRequest]),
                    'download_url' => route('api.exports.download', ['exportRequest' => $exportRequest]),
                ],
            ], 202);
        }

        return $pdfService->download(
            'applications::pdf.application-details',
            ['application' => $application],
            'application-' . $application->id . '.pdf'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'call_id' => ['required', 'integer', 'exists:call,id'],
            'team_id' => ['required', 'integer', 'exists:team,id'],
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer', 'distinct', 'exists:document,id'],
            'form_data' => ['nullable', 'array'],
            'form_data.*' => ['nullable', 'string', 'max:20000'],
        ]);

        $application = DB::transaction(function () use ($validated, $request) {
            $call = Call::query()
                ->with('callCriteria')
                ->whereKey($validated['call_id'])
                ->whereHas('currentStatusHistory.status', function ($query) {
                    $query->where('name', 'Publikované');
                })
                ->first();

            if ($call === null) {
                abort(422, 'Vybrana vyzva nie je publikovana.');
            }

            $team = Team::query()
                ->withCount('members')
                ->findOrFail((int) $validated['team_id']);

            if ($team->members_count < 3) {
                throw ValidationException::withMessages([
                    'team_id' => ['Tím musí mať aspoň 3 členov, aby bolo možné podať prihlášku.'],
                ]);
            }

            if (! $team->members()->where('user_id', $request->user()->id)->exists()) {
                throw ValidationException::withMessages([
                    'team_id' => ['Do prihlášky môžete použiť iba tím, ktorého ste členom.'],
                ]);
            }

            $langHeader = strtolower((string) $request->header('X-Locale', 'sk'));
            if (! in_array($langHeader, ['sk', 'en'], true)) {
                $langHeader = 'sk';
            }

            $language = Language::query()->where('name', $langHeader)->first()
                ?? Language::query()->where('name', 'sk')->firstOrFail();

            $call->loadMissing([
                'callCriteria.criterionTranslations:id,criterion_id,language_id,name',
            ]);

            $formDataInput = $validated['form_data'] ?? [];

            $storedFormData = CallFormSchema::normalizeStoredFormAnswers(
                $call,
                $language,
                $langHeader,
                $formDataInput
            );

            $unionIds = CallFormSchema::collectDocumentIdsFromStoredAnswers(
                $storedFormData,
                $call,
                $language,
                $langHeader
            );

            $requestIds = array_values(array_unique(array_map('intval', $validated['document_ids'])));
            sort($requestIds);

            if ($unionIds !== $requestIds) {
                throw ValidationException::withMessages([
                    'document_ids' => ['Zoznam príloh musí presne zodpovedať súborom priradeným v poliach formulára.'],
                ]);
            }

            $status = StatusOfApplication::query()->firstOrCreate([
                'name' => 'Podané',
            ]);

            $defaultType = TypeOfApplication::query()->firstOrCreate([
                'name' => 'Príloha prihlášky',
            ]);

            $publishedSchema = FormSchema::publishedLatestForCall((int) $call->id);

            $application = Application::query()->create([
                'submitted_at' => now(),
                'last_update' => now(),
                'call_id' => $validated['call_id'],
                'team_id' => $validated['team_id'],
                'created_by' => $request->user()->id,
                'active_status' => $status->id,
                'form_data' => $storedFormData === [] ? null : $storedFormData,
                'form_schema_id' => $publishedSchema?->id,
            ]);

            if ($publishedSchema !== null) {
                foreach ($publishedSchema->formFields as $formField) {
                    $key = $formField->name;
                    if (! array_key_exists($key, $storedFormData)) {
                        continue;
                    }
                    ApplicationAnswer::query()->create([
                        'application_id' => $application->id,
                        'form_field_id' => $formField->id,
                        'value' => $storedFormData[$key],
                    ]);
                }
            }

            $application->documents()->syncWithPivotValues(
                $validated['document_ids'],
                ['type_of_application_id' => $defaultType->id]
            );

            return $application->load([
                'call:id,name',
                'status:id,name',
                'team:id,name',
                'team.members.student.academicFlags',
                'documents:id',
                'documents.versions',
            ]);
        });

        $application = $this->workflowService()->submitApplication(
            $application,
            $request->user(),
            'Automaticky nastavene pri odoslani prihlasky.'
        );

        return (new ApplicationResource($application))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, int $id): ApplicationResource
    {
        $application = Application::query()->with(['status'])->findOrFail($id);
        $this->authorize('update', $application);

        // Only allow edit when status is 'Vyžiadané doplnenie' (pending supplement)
        $statusName = mb_strtolower((string) $application->status?->name);
        if (! str_contains($statusName, 'dopl')) {
            abort(403, 'Uprava je povolena len pri stave vyziadane doplnenie.');
        }

        $validated = $request->validate([
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer', 'distinct', 'exists:document,id'],
            'form_data' => ['nullable', 'array'],
            'form_data.*' => ['nullable', 'string', 'max:20000'],
        ]);

        $application = DB::transaction(function () use ($validated, $application) {
            $application->update([
                'form_data' => $validated['form_data'] ?? null,
                'last_update' => now(),
            ]);

            // Sync documents
            if (! empty($validated['document_ids'])) {
                $defaultType = TypeOfApplication::query()->firstOrCreate([
                    'name' => 'Príloha prihlášky',
                ]);

                $application->documents()->syncWithPivotValues(
                    $validated['document_ids'],
                    ['type_of_application_id' => $defaultType->id]
                );
            }

            return $application->load([
                'call:id,name',
                'status:id,name',
                'team:id,name',
                'team.members.student.academicFlags',
                'documents:id',
                'statusHistory.status:id,name',
            ]);
        });

        return new ApplicationResource($application);
    }

    public function submit(Request $request, int $id): ApplicationResource
    {
        $application = Application::query()->with(['status'])->findOrFail($id);
        $this->authorize('update', $application);

        $statusName = mb_strtolower((string) $application->status?->name);
        if (! str_contains($statusName, 'dopl')) {
            abort(403, 'Aplikacia moze byt znovu odoslana len po vyziadani doplnenia.');
        }

        $application = $this->workflowService()->submitApplication(
            $application,
            $request->user(),
            'Opätovné odoslanie prihlášky po doplnení.'
        );

        return new ApplicationResource($application->load([
            'call:id,name',
            'status:id,name',
            'team:id,name',
            'team.members.student.academicFlags',
            'documents:id',
            'statusHistory.status:id,name',
        ]));
    }

    public function updateStatus(Request $request, int $id): ApplicationResource
    {
        $application = Application::findOrFail($id);

        $this->authorize('changeStatus', $application);

        $validated = $request->validate([
            'status_id' => ['nullable', 'integer', 'exists:status_of_application,id', 'required_without:status_name'],
            'status_name' => ['nullable', 'string', 'max:120', 'required_without:status_id'],
            'note' => ['nullable', 'string'],
        ]);

        $application = $this->workflowService()->changeStatus(
            $application,
            $validated['status_id'] ?? null,
            $validated['status_name'] ?? null,
            $validated['note'] ?? null,
            $request->user()
        );

        return new ApplicationResource($application);
    }

}
