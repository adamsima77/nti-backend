<?php

namespace Modules\Programs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Services\Pdf\PdfService;
use Illuminate\Support\Facades\DB;
use Modules\Content\Models\Language;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Http\Resources\CallResource;
use Modules\Programs\Models\Call;
use Modules\Programs\Models\CallType;
use Modules\Programs\Models\StatusOfCall;
use Modules\Programs\Models\StatusOfCallHasCall;
use Modules\Programs\Support\CallFormSchema;
use Illuminate\Http\Response;

class CallController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $isAuthenticatedV1 = $request->user() && str_contains($request->path(), 'v1/');

        $calls = Call::query()
            ->withCount('applications')
            ->with([
                'program.typeOfProgram:id,name',
                'organization:id,name',
                'callType:id,name',
                'currentStatusHistory.status:id,name',
                'callTranslations.language:id,name',
                'callCriteria.criterionTranslations:id,criterion_id,language_id,name',
                'productOwner:id,name,email',
            ])

            ->when(
                !$isAuthenticatedV1,
                function ($q) use ($request) {
                    $q->whereHas('currentStatusHistory.status', function ($query) use ($request) {
                        $query->where('name', $request->filled('status')
                            ? $request->query('status')
                            : 'Publikované'
                        );
                    });
                }
            )
            ->when(
                $request->filled('deadline_from'),
                fn ($q) => $q->whereDate('application_deadline', '>=', $request->deadline_from)
            )
            ->when(
                $request->filled('deadline_to'),
                fn ($q) => $q->whereDate('application_deadline', '<=', $request->deadline_to)
            )
            ->when(
                $isAuthenticatedV1,
                function ($q) use ($request) {
                    $organizationId = $request->user()->organizations()->value('organization_id');
                    if ($organizationId) {
                        $q->where('organization_id', $organizationId);
                    }
                }
            )
            ->latest('id')
            ->paginate((int) $request->query('per_page', 15));

        return CallResource::collection($calls);
    }

    public function fetchCallByLang($lang)
    {
        $language = Language::where('name', $lang)->first();

        if (!$language) {
            return response()->json([
                'message' => 'Language not found!'
            ], 404);
        }

        $calls = Call::query()
            ->withCount('applications')
            ->with([
                'program.typeOfProgram:id,name',
                'organization:id,name',
                'callType:id,name',
                'callTranslations.language:id,name',
                'callCriteria.criterionTranslations:id,criterion_id,language_id,name',
                'productOwner:id,name,email',
            ])
            ->whereHas('currentStatusHistory.status', function ($query) {
                $query->where('name', 'Publikované');
            })
            ->paginate(15);

        $calls->getCollection()->transform(function ($call) use ($language, $lang) {

            $translation = $call->callTranslations
                ->firstWhere('language_id', $language->id);

            return [
                'id' => $call->id,
                'name' => $translation?->name ?? $call->name,
                'description' => $translation?->description ?? $call->description,

                'budget' => $call->budget,
                'budget_type' => $call->budget_type,
                'tech_spec' => $call->tech_spec,
                'tech_tags' => $call->tech_tags ?? [],
                'max_teams' => $call->max_teams,
                'product_owner' => [
                    'id' => $call->productOwner?->id,
                    'name' => $call->productOwner?->name,
                    'email' => $call->productOwner?->email,
                ],

                'application_start' => $call->application_start,
                'application_deadline' => $call->application_deadline,
                'project_start' => $call->project_start,
                'project_end' => $call->project_end,

                'is_open' => $call->application_deadline
                    ? now()->lt($call->application_deadline)
                    : false,

                'applicants_count' => $call->applications_count ?? 0,

                'program' => [
                    'id' => $call->program?->id,
                    'name' => $call->program?->typeOfProgram?->name,
                ],

                'organization' => [
                    'id' => $call->organization?->id,
                    'name' => $call->organization?->name,
                ],

                'call_type' => [
                    'id' => $call->callType?->id,
                    'name' => $call->callType?->name,
                ],

                'call_criteria' => collect($call->callCriteria)
                    ->map(fn ($criterion) => [
                        'id' => $criterion->id,
                        'name' => $criterion->criterionTranslations
                            ->firstWhere('language_id', $language->id)?->name,
                    ])
                    ->values(),

                'form_schema' => CallFormSchema::build($call, $language, $lang),
            ];
        });

        return response()->json($calls, 200);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Call::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],

            'application_start' => ['required', 'date'],
            'application_deadline' => ['required', 'date', 'after_or_equal:application_start'],
            'project_start' => ['required', 'date'],
            'project_end' => ['required', 'date', 'after_or_equal:project_start'],

            'program_id' => ['required', 'integer', 'exists:program,id'],
            'organization_id' => ['sometimes', 'integer', 'exists:organization,id'],
            'call_type_id' => ['sometimes', 'integer', 'exists:call_type,id'],

            'application_form_schema' => ['nullable', 'array'],

            'budget' => ['nullable', 'numeric', 'min:0'],
            'budget_type' => ['sometimes', 'string'],
            'tech_spec' => ['nullable', 'string'],
            'tech_tags' => ['nullable', 'array'],
            'max_teams' => ['sometimes', 'integer', 'min:1'],
            'po_email' => ['nullable', 'email', 'exists:users,email'],

            'translations' => ['sometimes', 'array'],
            'translations.*.language_id' => ['required', 'integer', 'exists:languages,id'],
            'translations.*.name' => ['required', 'string', 'max:255'],
            'translations.*.description' => ['required', 'string'],
        ]);

        $validated['call_type_id'] = $validated['call_type_id'] ?? CallType::query()->value('id');
        if (! $validated['call_type_id']) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Call type is not configured.');
        }

        if (empty($validated['organization_id']) && $request->user()) {
            $validated['organization_id'] = $request->user()->organizations()->value('organization_id');
        }

        if ($request->filled('po_email')) {
            $poUser = User::where('email', $request->po_email)->first();
            $validated['po_user_id'] = $poUser?->id;
        }

        return DB::transaction(function () use ($validated) {

            $call = Call::create(
                collect($validated)->except('translations')->toArray()
            );

            if (!empty($validated['translations'])) {
                foreach ($validated['translations'] as $translation) {
                    $call->callTranslations()->create($translation);
                }
            }

            $draftStatus = StatusOfCall::where('name', 'Draft')->firstOrFail();
            StatusOfCallHasCall::create([
                'call_id' => $call->id,
                'status_of_call_id' => $draftStatus->id,
            ]);

            return response()->json(
                $call->load('callTranslations'),
                201
            );
        });
    }

    public function update(Request $request, int $id)
    {
        $call = Call::findOrFail($id);
        $this->authorize('update', $call);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],

            'application_start' => ['sometimes', 'date'],
            'application_deadline' => ['sometimes', 'date'],
            'project_start' => ['sometimes', 'date'],
            'project_end' => ['sometimes', 'date'],

            'program_id' => ['sometimes', 'integer', 'exists:program,id'],
            'organization_id' => ['sometimes', 'integer', 'exists:organization,id'],
            'call_type_id' => ['sometimes', 'integer', 'exists:call_type,id'],

            'application_form_schema' => ['nullable', 'array'],

            'budget' => ['nullable', 'numeric', 'min:0'],
            'budget_type' => ['sometimes', 'string'],
            'tech_spec' => ['nullable', 'string'],
            'tech_tags' => ['nullable', 'array'],
            'max_teams' => ['sometimes', 'integer', 'min:1'],
            'po_email' => ['nullable', 'email', 'exists:users,email'],

            'translations' => ['sometimes', 'array'],
            'translations.*.language_id' => ['required_with:translations', 'integer', 'exists:languages,id'],
            'translations.*.name' => ['required_with:translations', 'string', 'max:255'],
            'translations.*.description' => ['required_with:translations', 'string'],
        ]);

        if (empty($validated['organization_id']) && $request->user()) {
            $validated['organization_id'] = $request->user()->organizations()->value('organization_id');
        }

        if ($request->filled('po_email')) {
            $poUser = User::where('email', $request->po_email)->first();
            $validated['po_user_id'] = $poUser?->id;
        }

        return DB::transaction(function () use ($validated, $id) {

            $call = Call::findOrFail($id);

            $call->update(
                collect($validated)->except('translations')->toArray()
            );

            if (!empty($validated['translations'])) {
                foreach ($validated['translations'] as $translation) {

                    $call->callTranslations()->updateOrCreate(
                        ['language_id' => $translation['language_id']],
                        [
                            'name' => $translation['name'],
                            'description' => $translation['description'],
                        ]
                    );
                }
            }

            return response()->json(
                $call->load('callTranslations')
            );
        });
    }

    public function show(Request $request, int $id)
    {
        $call = Call::query()
            ->withCount('applications')
            ->with([
                'program.typeOfProgram:id,name',
                'organization:id,name',
                'callType:id,name',
                'currentStatusHistory.status:id,name',
                'callCriteria.criterionTranslations:id,criterion_id,language_id,name',
                'callTranslations.language:id,name',
                'applications.team:id,name',
                'applications.status:id,name',
                'productOwner:id,name,email',
            ])
            ->findOrFail($id);

        $isOwner = false;
        if ($request->user()) {
            $isOwner = $request->user()->organizations()
                ->where('organization_id', $call->organization_id)
                ->exists();
        }

        if (! $isOwner) {
            $published = $call->currentStatusHistory?->status?->name === 'Publikované';
            if (! $published) {
                abort(Response::HTTP_NOT_FOUND);
            }
        }

        return new CallResource($call);
    }

    public function fetchCallByIdAndLang(int $id, string $lang)
    {
        $language = Language::where('name', $lang)->first();

        if (! $language) {
            return response()->json([
                'message' => 'Language not found!',
            ], 404);
        }

        $call = Call::query()
            ->withCount('applications')
            ->with([
                'program.typeOfProgram:id,name',
                'organization:id,name',
                'callType:id,name',
                'callCriteria.criterionTranslations:id,criterion_id,language_id,name',
                'callTranslations.language:id,name',
                'productOwner:id,name,email',
            ])
            ->whereHas('currentStatusHistory.status', function ($query) {
                $query->where('name', 'Publikované');
            })
            ->findOrFail($id);

        $translation = $call->callTranslations
            ->firstWhere('language_id', $language->id);

        return response()->json([
            'id' => $call->id,
            'name' => $translation?->name ?? $call->name,
            'description' => $translation?->description ?? $call->description,

            'budget' => $call->budget,
            'budget_type' => $call->budget_type,
            'tech_spec' => $call->tech_spec,
            'tech_tags' => $call->tech_tags ?? [],
            'max_teams' => $call->max_teams,
            'product_owner' => [
                'id' => $call->productOwner?->id,
                'name' => $call->productOwner?->name,
                'email' => $call->productOwner?->email,
            ],

            'application_start' => $call->application_start,
            'application_deadline' => $call->application_deadline,
            'project_start' => $call->project_start,
            'project_end' => $call->project_end,

            'is_open' => $call->application_deadline
                ? now()->lt($call->application_deadline)
                : false,

            'applicants_count' => $call->applications_count ?? $call->applications()->count(),

            'program' => [
                'id' => $call->program?->id,
                'name' => $call->program?->typeOfProgram?->name,
            ],

            'organization' => [
                'id' => $call->organization?->id,
                'name' => $call->organization?->name,
            ],

            'call_type' => [
                'id' => $call->callType?->id,
                'name' => $call->callType?->name,
            ],

            'call_criteria' => collect($call->callCriteria)
                ->map(function ($criterion) use ($language) {
                    $criterionTranslation = $criterion->criterionTranslations
                        ->firstWhere('language_id', $language->id);

                    return [
                        'id' => $criterion->id,
                        'name' => $criterionTranslation?->name,
                    ];
                })
                ->values(),

            'form_schema' => CallFormSchema::build($call, $language, $lang),
        ]);
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
            ->whereHas('currentStatusHistory.status', function ($query) {
                $query->where('name', 'Publikované');
            })
            ->findOrFail($id);

        return $pdfService->download(
            'programs::pdf.project-report',
            ['call' => $call],
            'project-report-' . $call->id . '.pdf'
        );
    }

    public function destroy(int $id)
    {
        $call = Call::findOrFail($id);

        $this->authorize('delete', $call);

        return DB::transaction(function () use ($call, $id) {

            DB::table('status_of_call_has_call')->where('call_id', $id)->delete();

            $call->delete();

            return response()->json([
                'message' => 'Call deleted successfully'
            ]);
        });
    }
}
