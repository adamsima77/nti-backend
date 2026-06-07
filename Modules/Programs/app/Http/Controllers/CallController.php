<?php

namespace Modules\Programs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\Pdf\PdfService;
use Modules\Content\Models\Language;
use Modules\Programs\Http\Resources\CallResource;
use Modules\Programs\Models\Call;
use Modules\Programs\Models\CallType;
use Modules\Programs\Models\StatusOfCall;
use Illuminate\Support\Facades\Log;



use Modules\Programs\Models\TypeOfProgram;
use Modules\Programs\StateMachines\CallStateMachine;
use Modules\Programs\StateMachines\CallStateMachineProgramA;
use Modules\Programs\Support\CallFormSchema;

/**
 * CallController
 *
 * ─── Concept map ────────────────────────────────────────────────────────────
 *
 * application_form_schema (JSON on Call)
 * └─ Fields applicants fill in when applying. Admin builds per call.
 * Program A calls are pre-seeded with the 6 mandatory document
 * upload slots (spec §7.3). Fields are never hard-disqualifiers.
 *
 * callCriteria  (BelongsToMany → call_has_criterion pivot)
 * └─ Scoring dimensions the evaluation committee uses.
 * Pivot columns:
 * weight            (1-10) — importance multiplier for scoring
 * is_academic_signal (bool) — flags informational-only criteria
 * (e.g. GPA, credit checks) that must
 * NOT auto-disqualify (spec §7.2)
 *
 * force_closed (bool on Call)
 * └─ Manual admin override. When true, is_open is always false
 * regardless of application_deadline. Reversible — admin can
 * toggle it back to false to re-open (if deadline still future).
 *
 * qualification_stack_id (FK on Call)
 * └─ Optional qualification stack assigned to the call.
 *
 * Mentors / Teams / Doc-signal scoring → separate modules (not here).
 * ────────────────────────────────────────────────────────────────────────────
 */
class CallController extends Controller
{
    use AuthorizesRequests;

    private const FIELD_TYPES = [
        'text', 'textarea', 'number', 'email',
        'select', 'radio', 'checkbox', 'date', 'file',
    ];

    private const PROGRAM_A_DEFAULT_FIELDS = [
        ['id' => 'doc_executive_summary',      'type' => 'file', 'label' => 'Executive Summary',      'name' => 'executive_summary',      'required' => true, 'help_text' => 'Stručný opis problému, riešenia, trhu a prínosu (PDF, max 5 MB)', 'accept' => '.pdf,.doc,.docx', 'options' => [], 'placeholder' => ''],
        ['id' => 'doc_technical_architecture', 'type' => 'file', 'label' => 'Technická architektúra', 'name' => 'technical_architecture', 'required' => true, 'help_text' => 'Opis riešenia, technológií, modulov a prevádzky',                  'accept' => '.pdf,.doc,.docx', 'options' => [], 'placeholder' => ''],
        ['id' => 'doc_roadmap',                'type' => 'file', 'label' => 'Roadmapa',               'name' => 'roadmap',                'required' => true, 'help_text' => 'Míľniky, plán realizácie a harmonogram',                          'accept' => '.pdf,.doc,.docx,.xlsx', 'options' => [], 'placeholder' => ''],
        ['id' => 'doc_budget',                 'type' => 'file', 'label' => 'Rozpočet',               'name' => 'budget',                 'required' => true, 'help_text' => 'Plán čerpania grantu a očakávané náklady',                        'accept' => '.pdf,.doc,.docx,.xlsx', 'options' => [], 'placeholder' => ''],
        ['id' => 'doc_risk_analysis',          'type' => 'file', 'label' => 'Riziková analýza',       'name' => 'risk_analysis',          'required' => true, 'help_text' => 'Identifikácia rizík, dopadov a mitigácií',                        'accept' => '.pdf,.doc,.docx', 'options' => [], 'placeholder' => ''],
        ['id' => 'doc_monetization_model',     'type' => 'file', 'label' => 'Monetizačný model',      'name' => 'monetization_model',     'required' => true, 'help_text' => 'Spôsob vytvárania hodnoty a príjmov produktu',                   'accept' => '.pdf,.doc,.docx', 'options' => [], 'placeholder' => ''],
    ];

    // ─────────────────────────────────────────────────────────────────────────
    //  ADMIN INDEX
    // ─────────────────────────────────────────────────────────────────────────

    public function adminIndex(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Call::class);

        /** @var \Modules\IdentityAccess\Models\User $user */
        $user = auth()->user();

        $query = Call::query()
            ->withCount('applications')
            ->with([
                'program.typeOfProgram:id,name',
                'organization:id,name',
                'currentStatusHistory.status:id,name',
                'callTranslations.language:id,name',
                'callCriteria',
                'qualificationStack.translations:id,name',
                'productOwner:id,name,email',
            ]);

        if (auth()->user()->isPartner()) {
            $orgId = $user->organizations()->value('organization_id');
            if ($orgId) {
                $query->where('organization_id', $orgId);
            }
        }

        if ($request->filled('status') && $request->query('status') !== 'all') {
            $query->whereHas('currentStatusHistory.status', fn ($q) =>
            $q->where('name', $request->query('status'))
            );
        }

        $query
            ->when($request->filled('deadline_from'), fn ($q) =>
            $q->whereDate('application_deadline', '>=', $request->deadline_from)
            )
            ->when($request->filled('deadline_to'), fn ($q) =>
            $q->whereDate('application_deadline', '<=', $request->deadline_to)
            )
            ->latest('id');

        $paginator = $query->paginate((int) $request->query('per_page', 15));

        return response()->json([
            'data'         => CallResource::collection($paginator->items()),
            'total'        => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PUBLIC INDEX
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $calls = Call::query()
            ->withCount('applications')
            ->with([
                'program.typeOfProgram:id,name',
                'organization:id,name',
                'currentStatusHistory.status:id,name',
                'callTranslations.language:id,name',
                'callCriteria.criterionTranslations:id,criterion_id,language_id,name',
            ])
            ->whereHas('currentStatusHistory.status', fn ($q) =>
            $q->where('name', $request->filled('status')
                ? $request->query('status')
                : 'Publikované')
            )
            ->when($request->filled('deadline_from'), fn ($q) =>
            $q->whereDate('application_deadline', '>=', $request->deadline_from)
            )
            ->when($request->filled('deadline_to'), fn ($q) =>
            $q->whereDate('application_deadline', '<=', $request->deadline_to)
            )
            ->latest('id')
            ->paginate((int) $request->query('per_page', 15));

        return response()->json(CallResource::collection($calls));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  SHOW — public (published only)
    // ─────────────────────────────────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        $call = Call::query()
            ->withCount('applications')
            ->with([
                'program.typeOfProgram:id,name',
                'organization:id,name',
                'currentStatusHistory.status:id,name',
                'callCriteria.criterionTranslations:id,criterion_id,language_id,name',
                'callCriteria' => function ($query) {
                    $query->withPivot('weight', 'is_academic_signal');
                },
                'callTranslations.language:id,name'
            ])
            ->findOrFail($id);

        return response()->json(new CallResource($call));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  SHOW — admin (any status, all relations for edit modal)
    // ─────────────────────────────────────────────────────────────────────────

    public function adminShow(int $id): JsonResponse
    {
        Log::info('adminShow called', ['id' => $id, 'user' => auth()->id()]);
        $this->authorize('view', Call::class);

        $call = Call::query()
            ->withCount('applications')
            ->with([
                'program.typeOfProgram:id,name',
                'organization:id,name',
                'callTranslations.language:id,name',
                'callType:id,name',
                'qualificationStack:id',
                'qualificationStack.translations:id,name,language_id',
                'currentStatusHistory.status:id,name',
                'callCriteria',
                'callCriteria.criterionTranslations:id,criterion_id,language_id,name,description',
                'productOwner:id,name,email',
                'documents.versions',
            ])
            ->findOrFail($id);

        $currentStatus = $call->currentStatusHistory?->status;

        // Build call_translations array (all languages)
        $callTranslations = $call->callTranslations->map(fn ($tr) => [
            'language_id' => $tr->language_id,
            'name'        => $tr->name,
            'description' => $tr->description ?? '',
        ])->values();

        // Build call_criteria with translations keyed by language_id
        $criteria = $call->callCriteria->map(function ($criterion) {
            $translations = [];
            foreach ($criterion->criterionTranslations as $tr) {
                $translations[$tr->language_id] = [
                    'name'        => $tr->name,
                    'description' => $tr->description ?? '',
                ];
            }

            return [
                'id'           => $criterion->id,
                'name'         => $criterion->name,
                'translations' => $translations,
                'pivot'        => [
                    'weight'             => $criterion->pivot?->weight ?? 1,
                    'is_academic_signal' => (bool) ($criterion->pivot?->is_academic_signal ?? false),
                ],
            ];
        })->values();

        // Build qualification_stack object with translations array
        $qualificationStack = null;
        if ($call->qualificationStack) {
            $qualificationStack = [
                'id'           => $call->qualificationStack->id,
                'translations' => $call->qualificationStack->translations->map(fn ($tr) => [
                    'language_id' => $tr->language_id,
                    'name'        => $tr->name,
                ])->values(),
            ];
        }

        $documents = $call->documents->map(fn ($doc) => [
            'id'   => $doc->id,
            'name' => $doc->versions()->latest('id')->first()?->file_name,
            'url'  => '/api/documents/' . $doc->id . '/download',
        ])->values();

        return response()->json([
            'id'                       => $call->id,
            'name'                     => $call->name,
            'description'              => $call->description,
            'budget'                   => $call->budget ? (float) $call->budget : null,
            'budget_type'              => $call->budget_type,
            'tech_spec'                => $call->tech_spec,
            'tech_tags'                => $call->tech_tags ?? [],
            'max_teams'                => $call->max_teams,
            'po_user_id'               => $call->po_user_id,
            'product_owner'            => [
                'id'    => $call->productOwner?->id,
                'name'  => $call->productOwner?->name,
                'email' => $call->productOwner?->email,
            ],
            'application_start'        => $call->application_start,
            'application_deadline'     => $call->application_deadline,
            'project_start'            => $call->project_start,
            'project_end'              => $call->project_end,
            'force_closed'             => (bool) $call->force_closed,
            'is_open'                  => !$call->force_closed && (
                $call->application_deadline
                    ? now()->lt($call->application_deadline)
                    : false
                ),
            'applicants_count'         => $call->applications_count ?? 0,

            'status'                   => [
                'id'   => $currentStatus?->id,
                'name' => $currentStatus?->name,
            ],

            'program'                  => [
                'id'   => $call->program?->id,
                'name' => $call->program?->typeOfProgram?->name,
            ],

            'organization'             => [
                'id'   => $call->organization?->id,
                'name' => $call->organization?->name,
            ],

            'program_id'               => $call->program_id,
            'status_id'                => $currentStatus?->id,
            'qualification_stack_id'   => $call->qualification_stack_id,

            'call_translations'        => $callTranslations,
            'call_criteria'            => $criteria,
            'qualification_stack'      => $qualificationStack,
            'application_form_schema'  => $call->application_form_schema,

            'call_type' => [
                'id'   => $call->callType?->id,
                'name' => $call->callType?->name,
            ],
            'documents'                => $documents,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  STORE
    // ─────────────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Call::class);

        $validated = $request->validate([
            'name'                    => ['required', 'string', 'max:255'],
            'description'             => ['nullable', 'string'],
            'budget'                  => ['nullable', 'numeric'],
            'budget_type'             => ['nullable', 'string'],
            'tech_spec'               => ['nullable', 'string'],
            'tech_tags'               => ['nullable', 'array'],
            'max_teams'               => ['nullable', 'integer'],
            'po_user_id'              => ['nullable', 'integer'],
            'po_email'                => ['nullable', 'email'],
            'organization_id'         => ['nullable', 'integer'],
            'application_start'       => ['required', 'date'],
            'application_deadline'    => ['required', 'date', 'after_or_equal:application_start'],
            'project_start'           => ['required', 'date'],
            'project_end'             => ['required', 'date', 'after_or_equal:project_start'],
            'program_id'              => ['required', 'integer', 'exists:program,id'],
            'status_id'               => ['nullable', 'integer', 'exists:status_of_call,id'],
            'language_id'             => ['required', 'integer', 'exists:languages,id'],
            'document_ids'            => ['nullable', 'array'],
            'document_ids.*'          => ['integer', 'exists:document,id'],

            // ── NEW: qualification stack ──────────────────────────────────
            'qualification_stack_id'  => ['nullable', 'integer', 'exists:qualification_stacks,id'],

            // Form schema
            'application_form_schema'                      => ['nullable', 'array'],
            'application_form_schema.fields'               => ['sometimes', 'array'],
            'application_form_schema.fields.*.id'          => ['required', 'string', 'max:100'],
            'application_form_schema.fields.*.type'        => ['required', Rule::in(self::FIELD_TYPES)],
            'application_form_schema.fields.*.label'       => ['required', 'string', 'max:255'],
            'application_form_schema.fields.*.name'        => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/'],
            'application_form_schema.fields.*.required'    => ['sometimes', 'boolean'],
            'application_form_schema.fields.*.help_text'   => ['nullable', 'string', 'max:500'],
            'application_form_schema.fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'application_form_schema.fields.*.options'     => ['sometimes', 'array'],
            'application_form_schema.fields.*.options.*'   => ['nullable', 'string', 'max:255'],
            'application_form_schema.fields.*.accept'      => ['nullable', 'string', 'max:255'],

            // Criteria with pivot data
            'criteria'                      => ['nullable', 'array'],
            'criteria.*.id'                 => ['required', 'integer', 'exists:criterion,id'],
            'criteria.*.weight'             => ['sometimes', 'integer', 'min:1', 'max:10'],
            'criteria.*.is_academic_signal' => ['sometimes', 'boolean'],

            'translations'               => ['sometimes', 'array'],
            'translations.*.language_id' => ['required', 'integer', 'exists:languages,id'],
            'translations.*.name'        => ['required', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
        ]);

        if (!empty($validated['application_form_schema']['fields'])) {
            $names = array_column($validated['application_form_schema']['fields'], 'name');
            if (count($names) !== count(array_unique($names))) {
                return response()->json([
                    'message' => 'Formulár obsahuje duplicitné názvy polí.',
                    'errors'  => ['application_form_schema.fields' => ['Duplicitné hodnoty name.']],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        if (empty($validated['organization_id']) && $request->user()) {
            $validated['organization_id'] = $request->user()->organizations()->value('organization_id');
        }

        try {
            return DB::transaction(function () use ($validated, $request): JsonResponse {

                $formSchema = $validated['application_form_schema'] ?? null;

                $callTypeId = $validated['call_type_id']
                    ?? $this->resolveCallTypeByProgram($validated['program_id'])->id;

                if ($request->filled('po_email')) {
                    $poUser = \Modules\IdentityAccess\Models\User::where('email', $request->po_email)->first();
                    $validated['po_user_id'] = $poUser?->id;
                }

                $call = Call::create([
                    'name'                    => $validated['name'],
                    'description'             => $validated['description'] ?? null,
                    'budget'                  => $validated['budget'] ?? null,
                    'budget_type'             => $validated['budget_type'] ?? 'milestone',
                    'tech_spec'               => $validated['tech_spec'] ?? null,
                    'tech_tags'               => $validated['tech_tags'] ?? [],
                    'max_teams'               => $validated['max_teams'] ?? 1,
                    'po_user_id'              => $validated['po_user_id'] ?? null,
                    'application_start'       => $validated['application_start'],
                    'application_deadline'    => $validated['application_deadline'],
                    'project_start'           => $validated['project_start'],
                    'project_end'             => $validated['project_end'],
                    'program_id'              => $validated['program_id'],
                    'organization_id'         => $validated['organization_id'] ?? null,
                    'call_type_id'            => $callTypeId,
                    'application_form_schema' => $formSchema,
                    'qualification_stack_id'  => $validated['qualification_stack_id'] ?? null,
                ]);

                $call->callTranslations()->create([
                    'language_id' => $validated['language_id'],
                    'name'        => $validated['name'],
                    'description' => $validated['description'] ?? '',
                ]);

                foreach ($validated['translations'] ?? [] as $tr) {
                    $call->callTranslations()->updateOrCreate(
                        ['language_id' => $tr['language_id']],
                        ['name' => $tr['name'], 'description' => $tr['description'] ?? '']
                    );
                }

                if (isset($validated['criteria'])) {
                    $call->callCriteria()->sync(
                        $this->buildCriteriaSyncData($validated['criteria'])
                    );
                }

                if (!empty($validated['document_ids'])) {
                    $call->documents()->sync($validated['document_ids']);
                }

                $draftStatus = StatusOfCall::where('name', CallStateMachineProgramA::STATE_DRAFT)->firstOrFail();
                $call->statusHistory()->create([
                    'status_of_call_id' => $draftStatus->id,
                    'note' => 'Počiatočný stav pri vytvorení.',
                ]);

                $targetStatusModel = StatusOfCall::find($validated['status_id'] ?? null);

                if ($targetStatusModel) {
                    $targetStateName = $targetStatusModel->name;

                    $callMachine = $validated['program_id'] == 1
                        ? new CallStateMachineProgramA($call)
                        : new CallStateMachine($call);

                    if ($targetStateName !== CallStateMachineProgramA::STATE_DRAFT) {
                        $callMachine->transitionTo($targetStateName, 'Automatický prechod pri vytvorení.');
                    }
                }

                return response()->json(
                    $call->load(['callTranslations', 'callCriteria']),
                    Response::HTTP_CREATED
                );
            });

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Neplatný stav výzvy alebo chýbajúce polia.',
                'errors'  => [
                    'status_id' => [$e->getMessage()]
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  UPDATE — Program A and Program B
    // ─────────────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id): JsonResponse
    {
        $call = Call::findOrFail($id);
        $this->authorize('update', $call);

        $validated = $request->validate([
            'name'                    => ['sometimes', 'string', 'max:255'],
            'description'             => ['nullable', 'string'],
            'budget'                  => ['nullable', 'numeric'],
            'budget_type'             => ['nullable', 'string'],
            'tech_spec'               => ['nullable', 'string'],
            'tech_tags'               => ['nullable', 'array'],
            'max_teams'               => ['nullable', 'integer'],
            'po_user_id'              => ['nullable', 'integer'],
            'po_email'                => ['nullable', 'email'],
            'application_start'       => ['sometimes', 'date'],
            'application_deadline'    => ['sometimes', 'date', 'after_or_equal:application_start'],
            'project_start'           => ['sometimes', 'date'],
            'project_end'             => ['sometimes', 'date', 'after_or_equal:project_start'],
            'program_id'              => ['sometimes', 'integer', 'exists:program,id'],
            'status_id'               => ['nullable', 'integer', 'exists:status_of_call,id'],
            'language_id'             => ['required', 'integer', 'exists:languages,id'],
            'document_ids'            => ['nullable', 'array'],
            'document_ids.*'          => ['integer', 'exists:document,id'],

            // Manual close override — reversible, admin can set back to false
            'force_closed'            => ['sometimes', 'boolean'],

            // ── NEW: qualification stack ──────────────────────────────────
            'qualification_stack_id'  => ['sometimes', 'nullable', 'integer', 'exists:qualification_stacks,id'],

            'application_form_schema'                      => ['nullable', 'array'],
            'application_form_schema.fields'               => ['required_with:application_form_schema', 'array'],
            'application_form_schema.fields.*.id'          => ['required', 'string', 'max:100'],
            'application_form_schema.fields.*.type'        => ['required', Rule::in(self::FIELD_TYPES)],
            'application_form_schema.fields.*.label'       => ['required', 'string', 'max:255'],
            'application_form_schema.fields.*.name'        => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/'],
            'application_form_schema.fields.*.required'    => ['sometimes', 'boolean'],
            'application_form_schema.fields.*.help_text'   => ['nullable', 'string', 'max:500'],
            'application_form_schema.fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'application_form_schema.fields.*.options'     => ['sometimes', 'array'],
            'application_form_schema.fields.*.options.*'   => ['nullable', 'string', 'max:255'],
            'application_form_schema.fields.*.accept'      => ['nullable', 'string', 'max:255'],

            // Criteria with pivot data
            'criteria'                      => ['nullable', 'array'],
            'criteria.*.id'                 => ['required', 'integer', 'exists:criterion,id'],
            'criteria.*.weight'             => ['sometimes', 'integer', 'min:1', 'max:10'],
            'criteria.*.is_academic_signal' => ['sometimes', 'boolean'],

            'translations'               => ['sometimes', 'array'],
            'translations.*.language_id' => ['required_with:translations', 'integer', 'exists:languages,id'],
            'translations.*.name'        => ['required_with:translations', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
        ]);

        if (!empty($validated['application_form_schema']['fields'])) {
            $names = array_column($validated['application_form_schema']['fields'], 'name');
            if (count($names) !== count(array_unique($names))) {
                return response()->json([
                    'message' => 'Formulár obsahuje duplicitné názvy polí.',
                    'errors'  => ['application_form_schema.fields' => ['Duplicitné hodnoty name.']],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        try{
        return DB::transaction(function () use ($validated, $call): JsonResponse {

            $call->update(
                collect($validated)
                    ->only([
                        'name', 'description', 'application_start',
                        'application_deadline', 'project_start', 'project_end',
                        'program_id', 'application_form_schema',
                        'force_closed',

                        'budget', 'budget_type',
                        'tech_spec', 'tech_tags',
                        'max_teams', 'po_user_id',
                        'organization_id',
                        // ── NEW ───────────────────────────────────────────
                        'qualification_stack_id',
                    ])
                    ->toArray()
            );

            $call->callTranslations()->updateOrCreate(
                ['language_id' => $validated['language_id']],
                [
                    'name'        => $validated['name']        ?? $call->name,
                    'description' => $validated['description'] ?? '',
                ]
            );

            foreach ($validated['translations'] ?? [] as $tr) {
                $call->callTranslations()->updateOrCreate(
                    ['language_id' => $tr['language_id']],
                    ['name' => $tr['name'], 'description' => $tr['description'] ?? '']
                );
            }

            $targetStatusModel = StatusOfCall::find($validated['status_id'] ?? null);
            if($targetStatusModel) {
                $callMachine = null;
                if($call->program_id == 1){
                    $callMachine = new CallStateMachineProgramA($call);
                } else if($call->program_id == 2){
                    $callMachine = new CallStateMachine($call);
                }

                if ($targetStatusModel->name !== $callMachine->currentState()) {
                    $callMachine->transitionTo($targetStatusModel->name, 'Zmena stavu cez editor výzvy.');
                }
            }

            if (array_key_exists('criteria', $validated)) {
                $call->callCriteria()->sync(
                    $this->buildCriteriaSyncData($validated['criteria'] ?? [])
                );
            }

            if (array_key_exists('document_ids', $validated)) {
                $call->documents()->sync($validated['document_ids'] ?? []);
            }

            return response()->json(
                $call->load(['callTranslations', 'callCriteria'])
            );
        });} catch (\Exception $e){
            return response()->json([
                'message' => 'Neplatný stav výzvy alebo chýbajúce polia.',
                'errors'  => [
                    'status_id' => [$e->getMessage()]
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  DESTROY
    // ─────────────────────────────────────────────────────────────────────────

    public function destroy(int $id): JsonResponse
    {
        $call = Call::findOrFail($id);
        $this->authorize('delete', $call);
        //$this->assertTypeA($call);

        return DB::transaction(function () use ($call): JsonResponse {
            $call->documents()->detach();
            DB::table('call_translations')->where('call_id', $call->id)->delete();
            DB::table('status_of_call_has_call')->where('call_id', $call->id)->delete();
            $call->callCriteria()->detach();
            $call->delete();

            return response()->json(['message' => 'Výzva bola úspešne zmazaná.']);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PUBLIC LANGUAGE-SPECIFIC FETCHERS
    // ─────────────────────────────────────────────────────────────────────────

    public function fetchCallByLang(string $lang): JsonResponse
    {
        $language = Language::where('name', $lang)->first();
        if (!$language) {
            return response()->json(['message' => 'Language not found!'], Response::HTTP_NOT_FOUND);
        }

        $calls = Call::query()
            ->withCount('applications')
            ->with([
                'program.typeOfProgram:id,name',
                'organization:id,name',
                'callType:id,name',
                'callTranslations.language:id,name',
                'callCriteria.criterionTranslations:id,criterion_id,language_id,name',
            ])
            ->whereHas('currentStatusHistory.status', fn ($q) =>
            $q->where('name', 'Publikované')
            )
            ->paginate(15);

        $calls->getCollection()->transform(function ($call) use ($language, $lang) {
            $now = Carbon::now();

            $isAfterStart = $call->application_start ? $now->greaterThanOrEqualTo(Carbon::parse($call->application_start)) : true;
            $isBeforeDeadline = $call->application_deadline ? $now->lessThanOrEqualTo(Carbon::parse($call->application_deadline)) : true;
            $notForceClosed = !((bool) $call->force_closed);

            $isOpen = $notForceClosed && $isAfterStart && $isBeforeDeadline;

            $formatted = $this->formatCallForLang($call, $language, $lang);
            $formattedArray = is_object($formatted) ? get_object_vars($formatted) : (array) $formatted;


            $formattedArray['is_open'] = $isOpen;
            $formattedArray['force_closed'] = !$notForceClosed;


            if (isset($formattedArray['form_schema'])) {
                $formattedArray['application_form_schema'] = $formattedArray['form_schema'];
            } elseif (isset($formattedArray['formSchema'])) {
                $formattedArray['application_form_schema'] = $formattedArray['formSchema'];
            } elseif (isset($call->application_form_schema)) {

                $formattedArray['application_form_schema'] = $call->application_form_schema;
            } else {
                $formattedArray['application_form_schema'] = null;
            }
            unset($formattedArray['formSchema'], $formattedArray['form_schema']);

            return $formattedArray;
        });

        return response()->json($calls);
    }

    public function fetchCallByIdAndLang(int $id, string $lang): JsonResponse
    {
        $language = Language::where('name', $lang)->first();
        if (!$language) {
            return response()->json(['message' => 'Language not found!'], Response::HTTP_NOT_FOUND);
        }

        $call = Call::query()
            ->withCount('applications')
            ->with([
                'program.typeOfProgram:id,name',
                'organization:id,name',
                'callType:id,name',
                'callCriteria.criterionTranslations:id,criterion_id,language_id,name',
                'callTranslations.language:id,name',
            ])
            ->whereHas('currentStatusHistory.status', fn ($q) =>
            $q->where('name', 'Publikované')
            )
            ->findOrFail($id);

        $isOpen = (bool) $call->is_open;
        $forceClosed = (bool) $call->force_closed;

        $formatted = $this->formatCallForLang($call, $language, $lang);

        $formattedArray = (array) $formatted;
        $formattedArray['is_open'] = $isOpen;
        $formattedArray['force_closed'] = $forceClosed;
        unset($formattedArray['formSchema'], $formattedArray['form_schema']);

        return response()->json($formattedArray);
    }

    public function downloadPdf(int $id, PdfService $pdfService)
    {
        $call = Call::query()
            ->with([
                'program.typeOfProgram:id,name',
                'organization:id,name',
                'currentStatusHistory.status:id,name',
                'callCriteria:id,name',
            ])
            ->whereHas('currentStatusHistory.status', fn ($q) =>
            $q->where('name', 'Publikované')
            )
            ->findOrFail($id);

        return $pdfService->download(
            'programs::pdf.project-report',
            ['call' => $call],
            'project-report-' . $call->id . '.pdf'
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function buildCriteriaSyncData(array $criteria): array
    {
        return collect($criteria)
            ->keyBy('id')
            ->map(fn ($c) => [
                'weight'             => max(1, min(10, (int) ($c['weight'] ?? 1))),
                'is_academic_signal' => (bool) ($c['is_academic_signal'] ?? false),
            ])
            ->toArray();
    }

    private function resolveTypeACallType(): CallType
    {
        $type = CallType::where('code', 'program_a')
            ->orWhere('name', 'Program A')
            ->first();

        abort_if(!$type, Response::HTTP_UNPROCESSABLE_ENTITY,
            'Typ výzvy pre Program A nebol nájdený v systéme.');

        return $type;
    }

    private function resolveCallTypeByProgram(int $programId): CallType
    {
        $program = \Modules\Programs\Models\Program::find($programId);
        $programName = $program?->typeOfProgram?->name ?? '';

        if (str_contains(strtolower($programName), 'b') || str_contains(strtolower($programName), 'prax')) {
            $type = CallType::where('code', 'program_b')
                ->orWhere('name', 'Firemné zadanie')
                ->first();
        } else {
            $type = CallType::where('code', 'program_a')
                ->orWhere('name', 'Program A')
                ->first();
        }

        abort_if(!$type, Response::HTTP_UNPROCESSABLE_ENTITY, 'Typ výzvy nebol nájdený.');

        return $type;
    }

    private function assertTypeA(Call $call): void
    {
        $typeA = $this->resolveTypeACallType();

        abort_if($call->call_type_id !== $typeA->id, Response::HTTP_FORBIDDEN,
            'Tento endpoint spravuje iba výzvy typu Program A.');
    }

    private function setInitialStatus(Call $call, ?int $statusId): void
    {
        $id = $statusId;

        if (empty($id)) {
            $draft = \Modules\Programs\Models\StatusOfCall::where('name', 'Draft')->first();
            $id = $draft?->id;
        }

        if ($id) {
            \Modules\Programs\Models\StatusOfCallHasCall::create([
                'call_id'           => $call->id,
                'status_of_call_id' => $id,
                'note'              => 'Inicializácia výzvy',
            ]);
        }
    }

    private function formatCallForLang(Call $call, Language $language, string $lang): array
    {
        $translation = $call->callTranslations->firstWhere('language_id', $language->id);

        return [
            'id'                      => $call->id,
            'name'                    => $translation?->name        ?? $call->name,
            'description'             => $translation?->description ?? $call->description,
            'budget'                  => $call->budget ? (float) $call->budget : null,
            'budget_type'             => $call->budget_type,
            'tech_tags'               => $call->tech_tags ?? [],
            'application_start'       => $call->application_start,
            'application_deadline'    => $call->application_deadline,
            'project_start'           => $call->project_start,
            'project_end'             => $call->project_end,
            'force_closed'            => (bool) $call->force_closed,
            'is_open'                 => !$call->force_closed && (
                $call->application_deadline
                    ? now()->lt($call->application_deadline)
                    : false
                ),
            'applicants_count'        => $call->applications_count ?? 0,
            'application_form_schema' => $call->application_form_schema,
            'program'      => ['id' => $call->program?->id,      'name' => $call->program?->typeOfProgram?->name],
            'organization' => ['id' => $call->organization?->id, 'name' => $call->organization?->name],
            'call_type'    => ['id' => $call->callType?->id,     'name' => $call->callType?->name],
            'call_criteria' => collect($call->callCriteria)
                ->map(fn ($c) => [
                    'id'                 => $c->id,
                    'name'               => $c->criterionTranslations->firstWhere('language_id', $language->id)?->name,
                    'weight'             => $c->pivot->weight ?? 1,
                    'is_academic_signal' => $c->pivot->is_academic_signal ?? false,
                ])
                ->values(),
            'form_schema' => CallFormSchema::build($call, $language, $lang),
        ];
    }
}
