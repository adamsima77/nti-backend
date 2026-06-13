<?php

namespace Modules\Programs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\Role;
use Modules\Organizations\Events\OrganizationMemberInvited;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationInvitation;
use App\Services\Pdf\PdfService;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\ApplicationStatusHistory;
use Modules\Applications\Models\StatusOfApplication;
use Modules\Evaluation\Models\CommissionMember;
use Modules\Evaluation\Models\Evaluation;
use Modules\Content\Models\Language;
use Modules\Organizations\Models\OrganizationRole;
use Modules\Programs\Http\Resources\CallResource;
use Modules\Programs\Models\Call;
use Modules\Programs\Models\CallType;
use Modules\Programs\Models\StatusOfCall;



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
                'productOwner:id,name,surname,email',
                'applications.team.members:id,name,surname',
                'applications.status:id,name',
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
                'callTranslations.language:id,name',
                'applications.team:id,name',
                'applications.status:id,name',
            ])
            ->findOrFail($id);

        return response()->json(new CallResource($call));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  SHOW — admin (any status, all relations for edit modal)
    // ─────────────────────────────────────────────────────────────────────────

    public function adminShow(int $id): JsonResponse
    {
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
                'productOwner:id,name,surname,email',
                'documents.versions',
                'applications.team:id,name',
                'applications.team.members:id',
                'applications.status:id,name',
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
            'po_user_id'               => $call->po_user_id,
            'product_owner'            => [
                'id'      => $call->productOwner?->id,
                'name'    => $call->productOwner?->name,
                'surname' => $call->productOwner?->surname,
                'email'   => $call->productOwner?->email,
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
            'applications'             => $call->applications->map(fn ($a) => [
                'id'            => $a->id,
                'team'          => [
                    'id'            => $a->team?->id,
                    'name'          => $a->team?->name,
                    'members_count' => $a->team?->members->count() ?? 0,
                ],
                'status'        => ['id' => $a->status?->id, 'name' => $a->status?->name],
                'submitted_at'  => $a->submitted_at,
            ])->values(),
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
                    $poUser = $this->resolveOrInvitePoUser(
                        $request->po_email,
                        $validated['organization_id'] ?? null,
                        $request->cookie('i18n_redirected', 'sk'),
                    );
                    $validated['po_user_id'] = $poUser->id;
                }

                $call = Call::create([
                    'name'                    => $validated['name'],
                    'description'             => $validated['description'] ?? null,
                    'budget'                  => $validated['budget'] ?? null,
                    'budget_type'             => $validated['budget_type'] ?? 'milestone',
                    'tech_spec'               => $validated['tech_spec'] ?? null,
                    'tech_tags'               => $validated['tech_tags'] ?? [],
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

                $this->syncPoUserOrgRole($call);

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
            'force_closed'            => ['sometimes', 'boolean'],
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

        // ── Guard: block program_id change once call has left Draft ───────────
        if (
            isset($validated['program_id']) &&
            (int) $validated['program_id'] !== (int) $call->program_id
        ) {
            $currentMachine = $call->program_id == 1
                ? new CallStateMachineProgramA($call)
                : new CallStateMachine($call);

            if ($currentMachine->currentState() !== CallStateMachine::STATE_DRAFT) {
                return response()->json([
                    'message' => 'Program nie je možné zmeniť po prechode z Draft stavu.',
                    'errors'  => ['program_id' => ['Výzva je už v stave, ktorý nezodpovedá novému programu.']],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        try {
            return DB::transaction(function () use ($validated, $call, $request): JsonResponse {

                if ($request->filled('po_email') && empty($validated['po_user_id'])) {
                    $poUser = $this->resolveOrInvitePoUser(
                        $request->po_email,
                        $validated['organization_id'] ?? $call->organization_id,
                        $request->cookie('i18n_redirected', 'sk'),
                    );
                    $validated['po_user_id'] = $poUser->id;
                }

                $call->update(
                    collect($validated)
                        ->only([
                            'name', 'description', 'application_start',
                            'application_deadline', 'project_start', 'project_end',
                            'program_id', 'application_form_schema',
                            'force_closed',
                            'budget', 'budget_type',
                            'tech_spec', 'tech_tags',
                            'po_user_id',
                            'organization_id',
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

                if ($targetStatusModel) {
                    $callMachine = $call->program_id == 1
                        ? new CallStateMachineProgramA($call)
                        : new CallStateMachine($call);

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

                $this->syncPoUserOrgRole($call->fresh());

                return response()->json(
                    $call->load(['callTranslations', 'callCriteria'])
                );
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Neplatný stav výzvy alebo chýbajúce polia.',
                'errors'  => ['status_id' => [$e->getMessage()]],
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
            DB::table('project_milestones')->where('call_id', $call->id)->delete();
            $appIds = $call->applications()->pluck('id');
            if ($appIds->isNotEmpty()) {
                DB::table('application_status_history')->whereIn('application_id', $appIds)->delete();
                DB::table('application_field_value')->whereIn('application_id', $appIds)->delete();
                DB::table('document_has_application')->whereIn('application_id', $appIds)->delete();
                DB::table('mentorship')->whereIn('application_id', $appIds)->delete();
                DB::table('evaluation')->whereIn('application_id', $appIds)->delete();
                DB::table('project_kpi')->whereIn('application_id', $appIds)->delete();
                DB::table('project_output')->whereIn('application_id', $appIds)->delete();
                DB::table('application')->whereIn('id', $appIds)->delete();
            }
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
    //  PROGRAM B — TEAM SELECTION
    // ─────────────────────────────────────────────────────────────────────────

    public function commissionSetup(int $id): JsonResponse
    {
        $this->authorize('viewAny', Call::class);

        $setup = DB::table('call_commission_setup')
            ->join('commission', 'commission.id', '=', 'call_commission_setup.commission_id')
            ->where('call_commission_setup.call_id', $id)
            ->select('commission.id as commission_id', 'commission.name as commission_name')
            ->first();

        $repMember = CommissionMember::where('call_id', $id)
            ->with(['user:id,name,surname,email', 'commission:id,name'])
            ->first();

        $locked = Call::findOrFail($id)->applications()
            ->whereHas('statusHistory', function ($q) {
                $q->where('status_of_application_id', function ($sq) {
                    $sq->select('id')->from('status_of_application')
                        ->where('name', 'V hodnotení')->limit(1);
                })->whereRaw('id = (select max(id) from application_status_history where application_id = application.id)');
            })
            ->exists();

        if (! $setup && ! $repMember) {
            return response()->json(['commission_setup' => null, 'locked' => $locked]);
        }

        if (! $setup && $repMember?->commission) {
            $setup = (object) [
                'commission_id'   => $repMember->commission->id,
                'commission_name' => $repMember->commission->name,
            ];
        }

        return response()->json([
            'commission_setup' => [
                'commission'  => ['id' => $setup->commission_id, 'name' => $setup->commission_name],
                'company_rep' => $repMember?->user ? [
                    'id'    => $repMember->user_id,
                    'name'  => trim($repMember->user->name . ' ' . $repMember->user->surname),
                    'email' => $repMember->user->email,
                ] : null,
            ],
            'locked' => $locked,
        ]);
    }

    public function orgMembers(int $id): JsonResponse
    {
        $this->authorize('viewAny', Call::class);

        $call = Call::with('organization.users')->findOrFail($id);

        $existingRep = CommissionMember::where('call_id', $id)
            ->with([
                'user:id,name,surname,email',
                'commission:id,name',
            ])
            ->first();

        $commissionSetup = null;
        if ($existingRep) {
            $commissionSetup = [
                'commission' => $existingRep->commission
                    ? ['id' => $existingRep->commission->id, 'name' => $existingRep->commission->name]
                    : null,
                'company_rep' => $existingRep->user ? [
                    'id'    => $existingRep->user_id,
                    'name'  => trim($existingRep->user->name . ' ' . $existingRep->user->surname),
                    'email' => $existingRep->user->email,
                ] : null,
            ];
        }

        if (! $call->organization) {
            return response()->json(['data' => [], 'commission_setup' => $commissionSetup]);
        }

        $members = $call->organization->users
            ->map(fn ($u) => [
                'id'    => $u->id,
                'name'  => trim($u->name . ' ' . $u->surname),
                'email' => $u->email,
            ]);

        return response()->json(['data' => $members, 'commission_setup' => $commissionSetup]);
    }


    public function setupCommission(Request $request, int $id): JsonResponse
    {
        $call = Call::with(['applications', 'organization', 'program.typeOfProgram'])->findOrFail($id);

        $this->authorize('update', $call);

        $isProgramB = str_contains(optional($call->program)->typeOfProgram?->name ?? '', 'B')
            || str_contains(optional($call->program)->name ?? '', 'B');

        // Lock commission changes once any application is in evaluation
        $hasEvaluatingApplication = $call->applications()
            ->whereHas('statusHistory', function ($q) {
                $q->where('status_of_application_id', function ($sq) {
                    $sq->select('id')->from('status_of_application')
                        ->where('name', 'V hodnotení')->limit(1);
                })->whereRaw('id = (select max(id) from application_status_history where application_id = application.id)');
            })
            ->exists();

        if ($hasEvaluatingApplication) {
            return response()->json([
                'message' => 'Komisiu nie je možné zmeniť — aspoň jedna prihláška je už v stave hodnotenia.',
            ], 422);
        }

        $validated = $request->validate([
            'commission_id'       => ['required', 'integer', 'exists:commission,id'],
            'company_rep_user_id' => [$isProgramB ? 'required' : 'nullable', 'integer', 'exists:users,id'],
        ]);

        if ($isProgramB && isset($validated['company_rep_user_id'])) {
            $belongs = DB::table('user_organization')
                ->where('user_id', $validated['company_rep_user_id'])
                ->where('organization_id', $call->organization_id)
                ->exists();

            if (! $belongs) {
                return response()->json([
                    'message' => 'Zástupca firmy musí byť členom organizácie, ktorá vytvorila túto výzvu.',
                ], 422);
            }
        }

        DB::transaction(function () use ($call, $validated, $isProgramB) {
            $commissionId     = $validated['commission_id'];
            $companyRepUserId = $validated['company_rep_user_id'] ?? null;

            // Remove old setup and evaluations if commission is being changed
            $oldSetup = DB::table('call_commission_setup')->where('call_id', $call->id)->first();
            if ($oldSetup) {
                $applicationIds = $call->applications()->pluck('id');

                // Delete evaluations for call-specific members (company rep)
                $callMemberIds = CommissionMember::where('call_id', $call->id)->pluck('id');
                Evaluation::whereIn('application_id', $applicationIds)
                    ->whereIn('commission_member_id', $callMemberIds)
                    ->delete();

                // Delete evaluations for regular members of the old commission
                $oldMemberIds = CommissionMember::where('commission_id', $oldSetup->commission_id)
                    ->whereNull('call_id')
                    ->pluck('id');
                Evaluation::whereIn('application_id', $applicationIds)
                    ->whereIn('commission_member_id', $oldMemberIds)
                    ->delete();

                CommissionMember::where('call_id', $call->id)->delete();
                DB::table('call_commission_setup')->where('call_id', $call->id)->delete();
            }

            DB::table('call_commission_setup')->insert([
                'call_id'       => $call->id,
                'commission_id' => $commissionId,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $regularMembers = CommissionMember::where('commission_id', $commissionId)
                ->whereNull('call_id')
                ->get();

            $orgMember = null;
            if ($isProgramB && $companyRepUserId) {
                $orgMember = CommissionMember::firstOrCreate([
                    'user_id'       => $companyRepUserId,
                    'commission_id' => $commissionId,
                    'call_id'       => $call->id,
                ]);
            }

            $applications = $call->applications()
                ->where('active_status', '!=', 1)
                ->get();

            foreach ($applications as $application) {
                foreach ($regularMembers as $member) {
                    Evaluation::firstOrCreate([
                        'application_id'       => $application->id,
                        'commission_member_id' => $member->id,
                    ], [
                        'decision_id'   => null,
                        'submitted_at'  => null,
                        'internal_note' => null,
                    ]);
                }

                if ($orgMember) {
                    Evaluation::firstOrCreate([
                        'application_id'       => $application->id,
                        'commission_member_id' => $orgMember->id,
                    ], [
                        'decision_id'   => null,
                        'submitted_at'  => null,
                        'internal_note' => null,
                    ]);
                }

            }
        });

        return response()->json(['message' => $isProgramB
            ? 'Komisia a zástupca firmy boli priradení k výzve.'
            : 'Komisia bola priradená k výzve.',
        ]);
    }

    public function programBApplications(int $id): JsonResponse
    {
        $this->authorize('viewAny', Call::class);

        $call = Call::with([
            'program.typeOfProgram:id,name',
            'currentStatusHistory.status:id,name',
        ])->findOrFail($id);

        $programName = $call->program?->typeOfProgram?->name ?? '';
        if (!str_contains(strtolower($programName), 'b')) {
            return response()->json(['message' => 'Výzva nepatrí do Programu B.'], 422);
        }

        $callStatus = $call->currentStatusHistory?->status?->name;
        if ($callStatus !== 'V párovaní') {
            return response()->json(['message' => 'Výzva sa nenachádza v stave "V párovaní".'], 422);
        }

        $commissionIds = CommissionMember::where('call_id', $id)
            ->pluck('commission_id')
            ->unique();

        $allMemberIds = CommissionMember::whereIn('commission_id', $commissionIds)
            ->where(function ($q) use ($id) {
                $q->whereNull('call_id')->orWhere('call_id', $id);
            })
            ->pluck('id');

        $totalMemberCount   = $allMemberIds->count();
        $commissionAssigned = $totalMemberCount > 0;

        $applications = Application::query()
            ->with([
                'team:id,name',
                'status:id,name',
                'evaluations' => fn ($q) => $q->with([
                    'scores:id,evaluation_id,criterion_id,score',
                    'commissionMember.user:id,name,surname',
                ]),
            ])
            ->where('call_id', $id)
            ->where('active_status', '!=', 1) // exclude drafts
            ->get();

        $data = $applications->map(function (Application $app) use ($totalMemberCount, $allMemberIds) {
            $evaluations    = $app->evaluations->filter(fn ($e) => $allMemberIds->contains($e->commission_member_id));
            $submittedCount = $evaluations->filter(fn ($e) => $e->submitted_at !== null)->count();
            $allEvaluated   = $totalMemberCount > 0 && $submittedCount === $totalMemberCount;

            $avgScore = null;
            if ($allEvaluated) {
                $allScores = $evaluations
                    ->flatMap(fn ($e) => $e->scores)
                    ->pluck('score')
                    ->filter(fn ($s) => $s !== null);
                $avgScore = $allScores->isNotEmpty() ? round($allScores->avg(), 2) : null;
            }

            $evaluationDetails = $evaluations->map(fn ($e) => [
                'evaluator'   => $e->commissionMember?->user
                    ? trim(($e->commissionMember->user->name ?? '') . ' ' . ($e->commissionMember->user->surname ?? ''))
                    : null,
                'submitted'   => $e->submitted_at !== null,
                'score_count' => $e->scores->count(),
                'avg_score'   => $e->scores->isNotEmpty()
                    ? round($e->scores->pluck('score')->filter()->avg(), 2)
                    : null,
            ]);

            return [
                'id'                          => $app->id,
                'reference'                   => $app->reference,
                'team'                        => $app->team ? ['id' => $app->team->id, 'name' => $app->team->name] : null,
                'status'                      => $app->status ? ['id' => $app->status->id, 'name' => $app->status->name] : null,
                'evaluations_count'           => $totalMemberCount,
                'submitted_evaluations_count' => $submittedCount,
                'all_evaluated'               => $allEvaluated,
                'average_score'               => $avgScore,
                'evaluations'                 => $evaluationDetails,
            ];
        });

        $allApplicationsEvaluated = $data->every(fn ($a) => $a['all_evaluated']);

        return response()->json([
            'data'                       => $data,
            'all_applications_evaluated' => $allApplicationsEvaluated,
            'commission_assigned'        => $commissionAssigned,
        ]);
    }

    public function selectTeam(Request $request, int $id): JsonResponse
    {
        $call = Call::with([
            'program.typeOfProgram:id,name',
            'currentStatusHistory.status:id,name',
        ])->findOrFail($id);

        $this->authorize('transition', $call);

        $validated = $request->validate([
            'application_id' => ['required', 'integer', 'exists:application,id'],
        ]);

        $programName = $call->program?->typeOfProgram?->name ?? '';
        if (!str_contains(strtolower($programName), 'b')) {
            return response()->json(['message' => 'Výzva nepatrí do Programu B.'], 422);
        }

        $callStatus = $call->currentStatusHistory?->status?->name;
        if ($callStatus !== 'V párovaní') {
            return response()->json(['message' => 'Výzva sa nenachádza v stave "V párovaní".'], 422);
        }

        $selectedApp = Application::where('id', $validated['application_id'])
            ->where('call_id', $id)
            ->firstOrFail();

        DB::transaction(function () use ($call, $selectedApp, $request) {
            $user            = $request->user();
            $approvedStatus  = StatusOfApplication::where('name', 'Schválené')->firstOrFail();
            $rejectedStatus  = StatusOfApplication::where('name', 'Zamietnuté')->firstOrFail();

            $selectedApp->update([
                'active_status' => $approvedStatus->id,
                'last_update'   => now(),
            ]);
            ApplicationStatusHistory::create([
                'status_of_application_id' => $approvedStatus->id,
                'application_id'           => $selectedApp->id,
                'note'                     => 'Tím bol vybraný administrátorom (Program B)',
                'changed_by'               => $user->id,
            ]);

            Application::where('call_id', $call->id)
                ->where('id', '!=', $selectedApp->id)
                ->where('active_status', '!=', 1)
                ->get()
                ->each(function (Application $app) use ($rejectedStatus, $user) {
                    $app->update([
                        'active_status' => $rejectedStatus->id,
                        'last_update'   => now(),
                    ]);
                    ApplicationStatusHistory::create([
                        'status_of_application_id' => $rejectedStatus->id,
                        'application_id'           => $app->id,
                        'note'                     => 'Iný tím bol vybraný administrátorom (Program B)',
                        'changed_by'               => $user->id,
                    ]);
                });

            (new CallStateMachine($call))->transitionTo('Pridelené');
        });

        return response()->json(['message' => 'Tím bol úspešne vybraný. Výzva bola presunutá do stavu "Pridelené".']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function resolveOrInvitePoUser(string $email, ?int $organizationId, string $lang = 'sk'): \Modules\IdentityAccess\Models\User
    {
        $email = mb_strtolower(trim($email));
        $user  = \Modules\IdentityAccess\Models\User::where('email', $email)->first();

        if (! $user) {
            $user = \Modules\IdentityAccess\Models\User::create([
                'name'      => preg_replace('/@.*$/', '', $email),
                'surname'   => '',
                'email'     => $email,
                'password'  => Hash::make(Str::random(32)),
                'status_id' => UserStatus::PENDING_EMAIL->value,
            ]);
        }

        $orgRole = Role::where('name', 'organization')->first();
        if ($orgRole && ! $user->roles()->where('name', 'organization')->exists()) {
            $user->roles()->attach($orgRole->id);
        }

        if ($organizationId) {
            $poRole      = OrganizationRole::where('name', 'org_product_owner')->firstOrFail();
            $organization = Organization::find($organizationId);

            $memberExists = DB::table('user_organization')
                ->where('user_id', $user->id)
                ->where('organization_id', $organizationId)
                ->exists();

            if ($memberExists) {
                DB::table('user_organization')
                    ->where('user_id', $user->id)
                    ->where('organization_id', $organizationId)
                    ->update(['organization_role' => $poRole->id]);
            } else {
                DB::table('user_organization')->insert([
                    'user_id'           => $user->id,
                    'organization_id'   => $organizationId,
                    'organization_role' => $poRole->id,
                ]);

                if ($organization) {
                    $invite = OrganizationInvitation::create([
                        'token'                => Str::random(64),
                        'email'                => $email,
                        'organization_id'      => $organizationId,
                        'organization_role_id' => $poRole->id,
                        'expires_at'           => now()->addHours(72),
                    ]);

                    event(new OrganizationMemberInvited(
                        invitation:   $invite,
                        organization: $organization,
                        roleLabel:    'Product Owner',
                        lang:         $lang === 'en' ? 'en' : 'sk',
                    ));
                }
            }
        }

        return $user;
    }

    private function syncPoUserOrgRole(Call $call): void
    {
        if (! $call->po_user_id || ! $call->organization_id) {
            return;
        }

        $poRole = OrganizationRole::where('name', 'org_product_owner')->first();
        if (! $poRole) {
            return;
        }

        $exists = DB::table('user_organization')
            ->where('user_id', $call->po_user_id)
            ->where('organization_id', $call->organization_id)
            ->exists();

        if ($exists) {
            DB::table('user_organization')
                ->where('user_id', $call->po_user_id)
                ->where('organization_id', $call->organization_id)
                ->update(['organization_role' => $poRole->id]);
        } else {
            DB::table('user_organization')->insert([
                'user_id'           => $call->po_user_id,
                'organization_id'   => $call->organization_id,
                'organization_role' => $poRole->id,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

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
