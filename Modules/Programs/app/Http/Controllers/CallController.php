<?php

namespace Modules\Programs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\Pdf\PdfService;
use Modules\Content\Models\Language;
use Modules\Programs\Http\Resources\CallResource;
use Modules\Programs\Models\Call;
use Modules\Programs\Models\CallType;
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

        $query = Call::query()
            ->withCount('applications')
            ->with([
                'program.typeOfProgram:id,name',
                'organization:id,name',
                'currentStatusHistory.status:id,name',
                'callTranslations.language:id,name',
                'callCriteria',
            ]);

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
                'callType:id,name',
                'currentStatusHistory.status:id,name',
                'callCriteria.criterionTranslations:id,criterion_id,language_id,name',
                'callTranslations.language:id,name',
            ])
            ->findOrFail($id);

        return response()->json(new CallResource($call));
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  STORE — Program A ONLY
    // ─────────────────────────────────────────────────────────────────────────

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Call::class);

        $validated = $request->validate([
            'name'                    => ['required', 'string', 'max:255'],
            'description'             => ['nullable', 'string'],
            'application_start'       => ['required', 'date'],
            'application_deadline'    => ['required', 'date', 'after_or_equal:application_start'],
            'project_start'           => ['required', 'date'],
            'project_end'             => ['required', 'date', 'after_or_equal:project_start'],
            'program_id'              => ['required', 'integer', 'exists:program,id'],
            'status_id'               => ['nullable', 'integer', 'exists:status_of_call,id'],
            'language_id'             => ['required', 'integer', 'exists:languages,id'],

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

        $programTypeA = $this->resolveTypeACallType();

        return DB::transaction(function () use ($validated, $programTypeA): JsonResponse {

            $formSchema = $validated['application_form_schema'] ?? null;

            $call = Call::create([
                'name'                    => $validated['name'],
                'description'             => $validated['description'] ?? null,
                'application_start'       => $validated['application_start'],
                'application_deadline'    => $validated['application_deadline'],
                'project_start'           => $validated['project_start'],
                'project_end'             => $validated['project_end'],
                'program_id'              => $validated['program_id'],
                'organization_id'         => null,
                'call_type_id'            => $programTypeA->id,
                'application_form_schema' => $formSchema,
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

            $this->setInitialStatus($call, $validated['status_id'] ?? null);

            return response()->json(
                $call->load(['callTranslations', 'callCriteria']),
                Response::HTTP_CREATED
            );
        });
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
            'application_start'       => ['sometimes', 'date'],
            'application_deadline'    => ['sometimes', 'date', 'after_or_equal:application_start'],
            'project_start'           => ['sometimes', 'date'],
            'project_end'             => ['sometimes', 'date', 'after_or_equal:project_start'],
            'program_id'              => ['sometimes', 'integer', 'exists:program,id'],
            'status_id'               => ['nullable', 'integer', 'exists:status_of_call,id'],
            'language_id'             => ['required', 'integer', 'exists:languages,id'],

            // Manual close override — reversible, admin can set back to false
            'force_closed'            => ['sometimes', 'boolean'],

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

        return DB::transaction(function () use ($validated, $call): JsonResponse {

            $call->update(
                collect($validated)
                    ->only([
                        'name', 'description', 'application_start',
                        'application_deadline', 'project_start', 'project_end',
                        'program_id', 'application_form_schema',
                        'force_closed',
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

            if (isset($validated['status_id']) && (int) $validated['status_id'] > 0) {
                $call->statusHistory()->create([
                    'status_of_call_id' => (int) $validated['status_id'],
                ]);
            }

            if (array_key_exists('criteria', $validated)) {
                $call->callCriteria()->sync(
                    $this->buildCriteriaSyncData($validated['criteria'] ?? [])
                );
            }

            return response()->json(
                $call->load(['callTranslations', 'callCriteria'])
            );
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  DESTROY
    // ─────────────────────────────────────────────────────────────────────────

    public function destroy(int $id): JsonResponse
    {
        $call = Call::findOrFail($id);
        $this->authorize('delete', $call);
        $this->assertTypeA($call);

        return DB::transaction(function () use ($call): JsonResponse {
            $call->callTranslations()->delete();
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
            // 1. Capture the structural state flags from the original Eloquent model
            $isOpen = (bool) $call->is_open;
            $forceClosed = (bool) $call->force_closed;

            // 2. Run your existing language formatter
            $formatted = $this->formatCallForLang($call, $language, $lang);

            // 3. Convert to array if it's an object/stdClass to safely drop/add keys
            $formattedArray = (array) $formatted;

            // 4. Inject the states and strip the sensitive schema
            $formattedArray['is_open'] = $isOpen;
            $formattedArray['force_closed'] = $forceClosed;
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

        // 1. Capture the structural state flags from the original Eloquent model
        $isOpen = (bool) $call->is_open;
        $forceClosed = (bool) $call->force_closed;

        // 2. Run your existing language formatter
        $formatted = $this->formatCallForLang($call, $language, $lang);

        // 3. Convert to array, inject state, and remove schema
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

    private function assertTypeA(Call $call): void
    {
        $typeA = $this->resolveTypeACallType();

        abort_if($call->call_type_id !== $typeA->id, Response::HTTP_FORBIDDEN,
            'Tento endpoint spravuje iba výzvy typu Program A.');
    }

    private function setInitialStatus(Call $call, ?int $statusId): void
    {
        if (!empty($statusId)) {
            \Modules\Programs\Models\StatusOfCallHasCall::create([
                'call_id'           => $call->id,
                'status_of_call_id' => (int) $statusId,
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
