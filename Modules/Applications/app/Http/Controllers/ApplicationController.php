<?php

namespace Modules\Applications\Http\Controllers;

use App\Services\Exports\QueuedExportService;
use App\Http\Controllers\Controller;
use App\Services\ApplicationWorkflowService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\Pdf\PdfService;
use Modules\Applications\Http\Resources\ApplicationResource;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\ApplicationAnswer;
use Modules\Applications\Models\Applications;
use Modules\Applications\Models\StatusOfApplication;
use Modules\Applications\Models\TypeOfApplication;
use Modules\Applications\StateMachines\ApplicationStateMachine;
use Modules\Content\Models\Language;
use Modules\Evaluation\Models\Commission;
use Modules\Evaluation\Models\CommissionMember;
use Modules\Evaluation\Models\Evaluation;
use Modules\Evaluation\Models\EvaluationScore;
use Modules\IdentityAccess\Models\User;
use Modules\Mentorship\Models\Mentorship;
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

    public function updateStateAdmin(Request $request, Application $application)
    {
        $this->authorize('update', $application);

        $request->validate([
            'state_id' => ['required', 'integer', 'exists:status_of_application,id'],
        ]);

        $targetStatusModel = StatusOfApplication::findOrFail($request->state_id);
        $targetStateName = $targetStatusModel->name;

        $stateMachine = new ApplicationStateMachine($application, $request->user());

        if ($stateMachine->currentState() === $targetStateName) {
            return response()->json([
                'message' => 'Aplikácia sa už nachádza v tomto stave.'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$stateMachine->canTransitionTo($targetStateName)) {
            return response()->json([
                'message' => "Prechod do stavu '{$targetStateName}' nie je povolený!"
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            DB::transaction(function () use ($targetStateName, $stateMachine, $request) {
                $note = $request->input('note', null);
                $stateMachine->transitionTo($targetStateName, $note);
            });

            return response()->json([
                'message' => 'Stav úspešne zmenený!'
            ], Response::HTTP_OK);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // DRAFT
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/applications/draft?call_id=&team_id=
     */
    public function getDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'call_id' => ['required', 'integer'],
            'team_id' => ['required', 'integer'],
        ]);

        $status = StatusOfApplication::query()->where('name', ApplicationStateMachine::STATE_DRAFT)->first();

        if (! $status) {
            return response()->json(['draft' => null]);
        }

        $draft = Application::query()
            ->with(['call:id,name', 'status:id,name', 'team:id,name', 'answers'])
            ->where('call_id', $validated['call_id'])
            ->where('team_id', $validated['team_id'])
            ->where('created_by', $request->user()->id)
            ->where('active_status', $status->id)
            ->first();

        return response()->json([
            'draft' => $draft ? new ApplicationResource($draft) : null,
        ]);
    }

    /**
     * POST /api/applications/draft
     *
     * Creates or updates a draft application for call+team.
     * On every save the existing answer row is replaced (not appended) so the
     * answers table stays clean — one row per draft application.
     */
    public function storeDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'call_id'     => ['required', 'integer', 'exists:call,id'],
            'team_id'     => ['required', 'integer', 'exists:team,id'],
            'form_data'   => ['nullable', 'array'],
            'form_data.*' => ['nullable', 'string', 'max:20000'],
        ]);

        $user = $request->user();
        $team = Team::query()->findOrFail($validated['team_id']);

        // Guard: current user must be a team member
        if (! $team->members()->where('user_id', $user->id)->exists()) {
            abort(403, 'Nie ste členom tohto tímu.');
        }

        $draftStatus = StatusOfApplication::query()
            ->where('name', ApplicationStateMachine::STATE_DRAFT)
            ->firstOrFail();

        // Block save if a non-draft application already exists for this call+team
        $alreadySubmitted = Application::query()
            ->where('call_id', $validated['call_id'])
            ->where('team_id', $validated['team_id'])
            ->where('active_status', '!=', $draftStatus->id)
            ->exists();

        if ($alreadySubmitted) {
            abort(422, 'Pre túto výzvu už vaša prihláška existuje alebo bola podaná.');
        }

        $draft = DB::transaction(function () use ($validated, $user, $draftStatus) {
            // Match on call+team only — not created_by, to avoid duplicate rows when
            // multiple team members save the same draft.
            $application = Application::query()->updateOrCreate(
                [
                    'call_id'       => $validated['call_id'],
                    'team_id'       => $validated['team_id'],
                    'active_status' => $draftStatus->id,
                ],
                [
                    'created_by'  => $user->id,
                    'last_update' => now(),
                ]
            );

            // Replace the answer row entirely — never accumulate rows on every save
            $application->answers()->delete();

            if (! empty($validated['form_data'])) {
                $application->answers()->create([
                    'answer' => $validated['form_data'],
                ]);
            }

            return $application;
        });

        return response()->json([
            'draft' => new ApplicationResource(
                $draft->load(['call:id,name', 'status:id,name', 'team:id,name', 'answers'])
            ),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // INDEX / SHOW
    // ──────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->authorize('viewAny', Applications::class);

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $applications = Application::query()
            ->with([
                'call:id,name',
                'status:id,name',
                'team:id,name',
                'team.members.student.academicFlags',
                'documents:id',
                'category.categoryTranslations:id,category_id,language_id,name',
            ])
            ->where('created_by', $request->user()->id)
            ->when(
                $request->filled('search'),
                fn (Builder $query) => $query->whereHas(
                    'call',
                    fn (Builder $q) => $q->where('name', 'ilike', '%'.$request->query('search').'%')
                )
            )
            ->when(
                $request->filled('program_type_id'),
                fn (Builder $query) => $query->whereHas(
                    'call.program',
                    fn (Builder $q) => $q->where('type_of_program_id', (int) $request->query('program_type_id'))
                )
            )
            ->when(
                $request->filled('status_id'),
                fn (Builder $query) => $query->where('active_status', (int) $request->query('status_id'))
            )
            ->latest('id')
            ->paginate($perPage);

        return ApplicationResource::collection($applications);
    }

    public function fetchForAdmin(Request $request)
    {
        $this->authorize('viewAny', Applications::class);

        $applications = Application::with([
            'status:id,name',
            'team:id,name',
            'call:id,name',
            'mentorships.mentor:id,name,surname'
        ])
            ->when(
                $request->filled('status_id'),
                fn ($q) => $q->where('active_status', $request->integer('status_id'))
            )
            ->paginate(15);

        return response()->json(['applications' => $applications], Response::HTTP_OK);
    }

    /**
     * GET /api/applications/{id}
     */
    public function show(Request $request, int $id): JsonResponse
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
                'category.categoryTranslations:id,category_id,language_id,name',
                'team.members.student',
                'mentorships.mentor',
                'evaluations.commissionMember.commission',
                'evaluations.commissionMember.user:id,name,surname',
                'evaluations.scores.criterion',
            ])
            ->findOrFail($id);

        $this->authorize('view', $application);

        $formSchema = null;
        if ($application->call_id) {
            $formSchema = FormSchema::publishedLatestForCall($application->call_id)
                ?->load('formFields');
        }

        return response()->json([
            'application' => new ApplicationResource($application),
            'form_schema'  => $formSchema,
        ]);
    }

    public function deleteMentor(Request $request, Application $application, Mentorship $mentorship)
    {
        $this->authorize('delete', $application);

        if ($mentorship->application_id !== $application->id) {
            abort(403, 'Mentorship nepatrí k tejto prihláške.');
        }

        $mentorship->sessions()->delete();
        $mentorship->delete();

        return response()->json(['message' => 'Mentor bol odstránený.'], 200);
    }

    public function removeCommittee(Request $request, Application $application)
    {
        $this->authorize('delete', $application);

        $evaluationIds = Evaluation::where('application_id', $application->id)->pluck('id');

        EvaluationScore::whereIn('evaluation_id', $evaluationIds)->delete();
        Evaluation::whereIn('id', $evaluationIds)->delete();

        return response()->json(['message' => 'Committee deleted from application!'], Response::HTTP_OK);
    }

    public function addCommittee(Request $request, Application $application, Commission $committee)
    {
        $this->authorize('addCommittee', $application);

        $comMembers = CommissionMember::where('commission_id', $committee->id)->get();

        foreach ($comMembers as $member) {
            Evaluation::updateOrCreate([
                'application_id'       => $application->id,
                'commission_member_id' => $member->id,
            ]);
        }

        return response()->json(['message' => 'Committee added to application!'], Response::HTTP_OK);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // STORE (submit new application)
    // ──────────────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'call_id'        => ['required', 'integer', 'exists:call,id'],
            'team_id'        => ['required', 'integer', 'exists:team,id'],
            'document_ids'   => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer', 'distinct', 'exists:document,id'],
            'form_data'      => ['nullable', 'array'],
            'form_data.*'    => ['nullable', 'string', 'max:20000'],
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

            $formDataInput  = $validated['form_data'] ?? [];
            $storedFormData = CallFormSchema::normalizeStoredFormAnswers($call, $language, $langHeader, $formDataInput);

            $unionIds   = CallFormSchema::collectDocumentIdsFromStoredAnswers($storedFormData, $call, $language, $langHeader);
            $requestIds = array_values(array_unique(array_map('intval', $validated['document_ids'])));
            sort($requestIds);

            if ($unionIds !== $requestIds) {
                throw ValidationException::withMessages([
                    'document_ids' => ['Zoznam príloh musí presne zodpovedať súborom priradeným v poliach formulára.'],
                ]);
            }

            $status      = StatusOfApplication::query()->firstOrCreate(['name' => 'Podané']);
            $defaultType = TypeOfApplication::query()->firstOrCreate(['name' => 'Príloha prihlášky']);

            $publishedSchema = FormSchema::publishedLatestForCall((int) $call->id);

            $application = Application::query()->create([
                'submitted_at'   => now(),
                'last_update'    => now(),
                'call_id'        => $validated['call_id'],
                'team_id'        => $validated['team_id'],
                'created_by'     => $request->user()->id,
                'active_status'  => $status->id,
                'form_data'      => $storedFormData === [] ? null : $storedFormData,
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
                        'form_field_id'  => $formField->id,
                        'value'          => $storedFormData[$key],
                    ]);
                }
            }

            $application->documents()->syncWithPivotValues(
                $validated['document_ids'],
                ['type_of_application_id' => $defaultType->id]
            );

            // Clean up the draft for this call+team so the list stays clean
            $draftStatus = StatusOfApplication::query()
                ->where('name', ApplicationStateMachine::STATE_DRAFT)
                ->first();

            if ($draftStatus) {
                Application::query()
                    ->where('call_id', $validated['call_id'])
                    ->where('team_id', $validated['team_id'])
                    ->where('created_by', $request->user()->id)
                    ->where('active_status', $draftStatus->id)
                    ->delete();
            }

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

    // ──────────────────────────────────────────────────────────────────────────
    // UPDATE (edit when status = Vyžiadané doplnenie)
    // ──────────────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id): ApplicationResource
    {
        $application = Application::query()->with(['status'])->findOrFail($id);
        $this->authorize('update', $application);

        $statusName = mb_strtolower((string) $application->status?->name);
        if (! str_contains($statusName, 'dopl')) {
            abort(403, 'Uprava je povolena len pri stave vyziadane doplnenie.');
        }

        $validated = $request->validate([
            'document_ids'   => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer', 'distinct', 'exists:document,id'],
            'form_data'      => ['nullable', 'array'],
            'form_data.*'    => ['nullable', 'string', 'max:20000'],
        ]);

        $application = DB::transaction(function () use ($validated, $application, $request) {
            $application->loadMissing([
                'call.callCriteria.criterionTranslations:id,criterion_id,language_id,name',
            ]);

            $langHeader = strtolower((string) $request->header('X-Locale', 'sk'));
            if (! in_array($langHeader, ['sk', 'en'], true)) {
                $langHeader = 'sk';
            }

            $language = Language::query()->where('name', $langHeader)->first()
                ?? Language::query()->where('name', 'sk')->first();

            $storedFormData = [];

            if (! empty($validated['form_data'])) {
                $storedFormData = CallFormSchema::normalizeStoredFormAnswers(
                    $application->call,
                    $language,
                    $langHeader,
                    $validated['form_data'] ?? []
                );

                $unionIds   = CallFormSchema::collectDocumentIdsFromStoredAnswers($storedFormData, $application->call, $language, $langHeader);
                $requestIds = array_values(array_unique(array_map('intval', $validated['document_ids'])));
                sort($requestIds);

                if ($unionIds !== $requestIds) {
                    throw ValidationException::withMessages([
                        'document_ids' => ['Zoznam príloh musí presne zodpovedať súborom priradeným v poliach formulára.'],
                    ]);
                }
            }

            $application->update([
                'form_data'   => $storedFormData === [] ? null : $storedFormData,
                'last_update' => now(),
            ]);

            if (! empty($validated['document_ids'])) {
                $defaultType = TypeOfApplication::query()->firstOrCreate(['name' => 'Príloha prihlášky']);
                $application->documents()->syncWithPivotValues(
                    $validated['document_ids'],
                    ['type_of_application_id' => $defaultType->id]
                );
            }

            if ($application->call !== null) {
                $publishedSchema = FormSchema::publishedLatestForCall((int) $application->call_id);
                if ($publishedSchema !== null) {
                    ApplicationAnswer::query()->where('application_id', $application->id)->delete();
                    foreach ($publishedSchema->formFields as $formField) {
                        $key = $formField->name;
                        if (! array_key_exists($key, $storedFormData)) {
                            continue;
                        }
                        ApplicationAnswer::query()->create([
                            'application_id' => $application->id,
                            'form_field_id'  => $formField->id,
                            'value'          => $storedFormData[$key],
                        ]);
                    }
                }
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

    // ──────────────────────────────────────────────────────────────────────────
    // SUBMIT (re-submit after Vyžiadané doplnenie)
    // ──────────────────────────────────────────────────────────────────────────

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

    // ──────────────────────────────────────────────────────────────────────────
    // OTHER
    // ──────────────────────────────────────────────────────────────────────────

    public function getApplicationAnswer(Request $request, Application $application)
    {
        $this->authorize('view', $application);

        $app_answer = ApplicationAnswer::where('application_id', $application->id)->firstOrFail();

        return response()->json($app_answer->answer);
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
                'application-'.$application->id.'.pdf',
                [
                    'model_class' => Application::class,
                    'model_id'    => $application->id,
                    'relations'   => [
                        'call:id,name',
                        'status:id,name',
                        'team:id,name',
                        'team.members.student.academicFlags',
                        'documents:id',
                        'statusHistory.status:id,name',
                    ],
                    'view'     => 'applications::pdf.application-details',
                    'data_key' => 'application',
                ]
            );

            return response()->json([
                'message'        => 'Generovanie exportu bolo zaradené do fronty.',
                'export_request' => [
                    'id'           => $exportRequest->id,
                    'export_key'   => $exportRequest->export_key,
                    'kind'         => $exportRequest->kind,
                    'format'       => $exportRequest->format,
                    'status'       => $exportRequest->status,
                    'file_name'    => $exportRequest->file_name,
                    'status_url'   => route('api.exports.show', ['exportRequest' => $exportRequest]),
                    'download_url' => route('api.exports.download', ['exportRequest' => $exportRequest]),
                ],
            ], 202);
        }

        return $pdfService->download(
            'applications::pdf.application-details',
            ['application' => $application],
            'application-'.$application->id.'.pdf'
        );
    }

    public function updateStatus(Request $request, int $id): ApplicationResource
    {
        $application = Application::findOrFail($id);
        $this->authorize('changeStatus', $application);

        $validated = $request->validate([
            'status_id'   => ['nullable', 'integer', 'exists:status_of_application,id', 'required_without:status_name'],
            'status_name' => ['nullable', 'string', 'max:120', 'required_without:status_id'],
            'note'        => ['nullable', 'string'],
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
