<?php

namespace Modules\Evaluation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\ApplicationWorkflowService;
use Modules\Applications\Models\Application;
use Modules\Evaluation\Models\Commission;
use Modules\Evaluation\Models\CommissionMember;
use Modules\Evaluation\Models\Decision;
use Modules\Evaluation\Models\Evaluation;
use Modules\Evaluation\Models\EvaluationScore;
use Modules\Content\Enums\LanguageType;
use Modules\Content\Models\Language;
use Modules\Organizations\Models\OrganizationRole;
use Modules\Programs\Models\Call;
use Modules\Programs\Models\Call as ProgramCall;
use Modules\Programs\Models\Criterion;
use Modules\Programs\Support\CallFormSchema;
use Modules\Teams\Models\TeamRole;

class EvaluationController extends Controller
{
    use AuthorizesRequests;

    public function fetchCommittes(Request $request){
        $this->authorize('viewAny', Evaluation::class);
        $com = Commission::all();
        return response()->json(['commissions' => $com], Response::HTTP_OK);
    }

    public function fetchForEvaluator(Request $request)
    {
        $this->authorize('viewEvaluations', Evaluation::class);
        $evaluator_id = $request->user()->id;

        $idOfEvaluator = CommissionMember::where('user_id', $evaluator_id)->first();

        if (!$idOfEvaluator) {
            return response()->json(['evaluations' => []], Response::HTTP_OK);
        }

        $evaluations = Evaluation::with([
            'application' => function ($query) use ($idOfEvaluator) {
                $query->with(['status']);
                $query->with([
                    'call' => function ($query) use ($idOfEvaluator) {
                        $query->withCount(['applications as vsetky_moje_na_hodnotenie_count' => function ($q) use ($idOfEvaluator) {
                            $q->whereHas('evaluations', function ($evalQuery) use ($idOfEvaluator) {
                                $evalQuery->where('commission_member_id', $idOfEvaluator->id);
                            });
                        }]);

                        $query->withCount(['applications as moje_uz_ohodnotene_count' => function ($q) use ($idOfEvaluator) {
                            $q->whereHas('evaluations', function ($evalQuery) use ($idOfEvaluator) {
                                $evalQuery->where('commission_member_id', $idOfEvaluator->id)
                                    ->whereNotNull('decision_id');
                            });
                        }]);
                    }
                ]);
            },
            'scores',
            'decision'
        ])
            ->where('commission_member_id', $idOfEvaluator->id)
            ->get();

        return response()->json(['evaluations' => $evaluations], Response::HTTP_OK);
    }



    public function assignCommission(Request $request, Application $application)
    {
        // 1. Očakávame ID komisie, ktorú chce admin prihláške priradiť
        $request->validate([
            'commission_id' => 'required|exists:commission,id',
            'org_user_id'   => 'nullable|integer|exists:users,id',
        ]);

        $commissionId = $request->input('commission_id');
        $orgUserId    = $request->input('org_user_id');

        // 2. Autorizácia
        $this->authorize('viewAny', Evaluation::class);

        // 3. Spustíme transakciu a vygenerujeme hárky pre každého člena tejto komisie
        DB::transaction(function () use ($application, $commissionId, $orgUserId) {

            $members = CommissionMember::where('commission_id', $commissionId)
                ->whereNull('call_id')
                ->get();

            foreach ($members as $member) {
                // Vytvoríme riadok v tabuľke evaluation presne tak, ako ho máš v nti=# SELECT * FROM evaluation
                Evaluation::firstOrCreate([
                    'application_id'       => $application->id,
                    'commission_member_id' => $member->id, // Prepája to na komisiu cez člena
                ], [
                    'decision_id'          => null,
                    'submitted_at'         => null,
                    'internal_note'        => null,
                ]);
            }

            if ($orgUserId && $application->call_id) {
                $callOrgId = $application->call?->organization_id
                    ?? ProgramCall::find($application->call_id)?->organization_id;

                $belongs = \Modules\Organizations\Models\UserOrganization::query()
                    ->where('user_id', $orgUserId)
                    ->where('organization_id', $callOrgId)
                    ->exists();

                if ($belongs) {
                    $orgMember = CommissionMember::firstOrCreate([
                        'user_id'       => $orgUserId,
                        'commission_id' => $commissionId,
                        'call_id'       => $application->call_id,
                    ]);

                    Evaluation::firstOrCreate([
                        'application_id'       => $application->id,
                        'commission_member_id' => $orgMember->id,
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Komisia bola úspešne priradená a hodnotiace hárky boli vygenerované.'
        ], 200);
    }

    private function normalizeProgramLabel(?string $programName): string
    {
        $normalized = mb_strtolower(trim((string) $programName));

        if (str_contains($normalized, 'program a')) {
            return 'A';
        }

        if (str_contains($normalized, 'program b')) {
            return 'B';
        }

        return $programName ? (string) $programName : '';
    }

    private function normalizeApplicationStatus(?string $statusName): string
    {
        $normalized = mb_strtolower(trim((string) $statusName));

        return match (true) {
            str_contains($normalized, 'draft') => 'draft',
            str_contains($normalized, 'pod') => 'submitted',
            str_contains($normalized, 'hodnot') => 'evaluating',
            str_contains($normalized, 'doplnen') => 'supplement',
            str_contains($normalized, 'schv') => 'approved',
            str_contains($normalized, 'zamiet') => 'rejected',
            default => $normalized !== '' ? $normalized : 'draft',
        };
    }

    private function resolveCommissionMemberIds(Request $request): Collection
    {
        return CommissionMember::query()
            ->where('user_id', $request->user()->id)
            ->pluck('id');
    }

    private function isOrgMember(Request $request): bool
    {
        return CommissionMember::query()
            ->where('user_id', $request->user()->id)
            ->whereNotNull('call_id')
            ->exists();
    }

    private function orgMemberCallIds(Request $request): array
    {
        return CommissionMember::query()
            ->where('user_id', $request->user()->id)
            ->whereNotNull('call_id')
            ->pluck('call_id')
            ->toArray();
    }

    private function resolveDecisionId(string $recommendation): int
    {
        $decisionName = match ($recommendation) {
            'approve' => 'Schválené',
            'reject' => 'Zamietnuté',
            'supplement' => 'Podmienečne schválené',
            default => 'Podmienečne schválené',
        };

        return (int) Decision::query()
            ->where('name', $decisionName)
            ->value('id');
    }

    private function evaluationTotal(Evaluation $evaluation): float
    {
        return (float) $evaluation->scores->sum('score');
    }

    private function resolveAcademicFlag(Application $application): ?bool
    {
        $application->loadMissing(['team.members.student.academicFlags']);

        return $application->academic_flag;
    }

    private function resolveAcademicRecord(Application $application): ?array
    {
        foreach ($application->team?->members ?? [] as $member) {
            $student = $member?->student;
            if ($student?->academicRecord !== null) {
                $record = $student->academicRecord;
                $transcriptUrl = $record->transcript_file ? Storage::url($record->transcript_file) : null;

                return [
                    'student_id' => $record->student_id,
                    'transcript_file' => $transcriptUrl,
                    'honor_declaration' => (bool) $record->honor_declaration,
                    'honor_declaration_signed_at' => optional($record->honor_declaration_signed_at)?->toDateTimeString(),
                    'school' => $student->university?->name ?? null,
                    'study_program' => $student->studyProgram?->studyProgramTranslations?->first()?->name ?? $student->studyProgram?->name ?? null,
                    'study_year' => $student->studyYear?->studyYearTranslations?->first()?->name ?? null,
                ];
            }
        }

        return null;
    }

    private function evaluationPayload(?Evaluation $evaluation, Collection $callCriteria): ?array
    {
        if ($evaluation === null) {
            return null;
        }

        $scoreMap = $evaluation->scores->keyBy('criterion_id');

        return [
            'id' => $evaluation->id,
            'application_id' => $evaluation->application_id,
            'criteria' => $callCriteria->map(function ($criterion) use ($scoreMap) {
                $score = $scoreMap->get($criterion->id);
                $translation = $criterion->criterionTranslations
                    ->firstWhere('language_id', LanguageType::SLOVAK->value);

                return [
                    'name' => $translation?->name ?? $criterion->name ?? ('Kritérium #'.$criterion->id),
                    'max_score' => 20,
                    'score' => $score?->score !== null ? (float) $score->score : null,
                    'comment' => $score?->comment ?? null,
                ];
            })->values(),
            'total_score' => $this->evaluationTotal($evaluation),
            'recommendation' => $evaluation->decision === null ? null : (
                $this->normalizeApplicationStatus($evaluation->decision->name) === 'approved'
                    ? 'approve'
                    : ($this->normalizeApplicationStatus($evaluation->decision->name) === 'rejected' ? 'reject' : 'supplement')
            ),
            'internal_note' => $evaluation->internal_note,
            'submitted_at' => optional($evaluation->submitted_at ?? $evaluation->created_at)?->toDateTimeString(),
            'locked' => $evaluation->submitted_at !== null,
        ];
    }

    private function workflowService(): ApplicationWorkflowService
    {
        return app(ApplicationWorkflowService::class);
    }

    private function applicationSummary(Application $application, ?int $myCommissionMemberId = null): array
    {
        $application->loadMissing([
            'call.program.typeOfProgram:id,name',
            'team.members',
            'team.members.student.academicFlags',
            'status:id,name',
            'category.categoryTranslations:id,category_id,language_id,name',
        ]);

        $evaluations = Evaluation::query()
            ->with('scores')
            ->where('application_id', $application->id)
            ->get();

        $myScore = null;
        $totals = [];

        foreach ($evaluations as $evaluation) {
            $total = $this->evaluationTotal($evaluation);
            $totals[] = $total;

            if ($myCommissionMemberId !== null && (int) $evaluation->commission_member_id === $myCommissionMemberId) {
                $myScore = $total;
            }
        }

        $avgScore = count($totals) ? array_sum($totals) / count($totals) : null;
        $team = $application->team;
        $programName = $application->call?->program?->typeOfProgram?->name;
        $submittedAt = $application->submitted_at ? $application->submitted_at->format('d.m.Y') : '';

        return [
            'id' => $application->id,
            'status' => $this->normalizeApplicationStatus($application->status?->name),
            'academic_flag' => $this->resolveAcademicFlag($application),
            'team' => [
                'id' => $team?->id,
                'name' => $team?->name ?? '',
                'members_count' => $team?->members?->count() ?? 0,
            ],
            'my_score' => $myScore,
            'submitted_at' => $submittedAt,
            'projectName' => $application->call?->name ?? $team?->name ?? ('Prihláška #'.$application->id),
            'teamName' => $team?->name ?? '',
            'program' => $this->normalizeProgramLabel($programName),
            'deadline' => $application->call?->application_deadline?->format('Y-m-d'),
            'avgScore' => $avgScore,
            'call_id' => $application->call_id,
            'category' => $application->category ? [
                'id' => $application->category->id,
                'name' => $application->category->categoryTranslations
                    ->firstWhere('language_id', LanguageType::SLOVAK->value)?->name
                    ?? $application->category->slug,
            ] : null,
        ];
    }

    private function callPayload(ProgramCall $call, ?int $currentCommissionMemberId = null): array
    {
        $applications = $call->applications()
            ->whereNotNull('submitted_at')
            ->with([
                'team.members',
                'call.program.typeOfProgram:id,name',
                'status:id,name',
            ])
            ->get();

        $evaluations = Evaluation::query()
            ->with('scores')
            ->whereIn('application_id', $applications->pluck('id'))
            ->get();

        $scoresByApplication = [];
        foreach ($evaluations as $evaluation) {
            $total = $this->evaluationTotal($evaluation);
            $scoresByApplication[$evaluation->application_id]['totals'][] = $total;

            if ($currentCommissionMemberId !== null && (int) $evaluation->commission_member_id === $currentCommissionMemberId) {
                $scoresByApplication[$evaluation->application_id]['my_score'] = $total;
            }
        }

        $criteria = $call->callCriteria->map(function ($criterion) {
            $translation = $criterion->criterionTranslations
                ->firstWhere('language_id', LanguageType::SLOVAK->value);

            return [
                'id' => $criterion->id,
                'name' => $translation?->name ?? $criterion->name ?? ('Kritérium #'.$criterion->id),
                'max_score' => 20,
            ];
        })->values();

        return [
            'id' => $call->id,
            'name' => $call->name,
            'program' => $this->normalizeProgramLabel($call->program?->typeOfProgram?->name),
            'status' => $this->normalizeApplicationStatus($call->currentStatusHistory?->status?->name),
            'deadline' => $call->application_deadline?->format('Y-m-d'),
            'applications_total' => $applications->count(),
            'applications_pending' => $applications->filter(function (Application $application) use ($scoresByApplication) {
                $status = $this->normalizeApplicationStatus($application->status?->name);
                $evaluated = array_key_exists($application->id, $scoresByApplication)
                    && array_key_exists('my_score', $scoresByApplication[$application->id]);

                return in_array($status, ['submitted', 'evaluating', 'supplement'], true) && ! $evaluated;
            })->count(),
            'applications_evaluated' => $applications->filter(function (Application $application) use ($scoresByApplication) {
                return array_key_exists($application->id, $scoresByApplication)
                    && array_key_exists('my_score', $scoresByApplication[$application->id]);
            })->count(),
            'criteria' => $criteria,
        ];
    }

    private function resolveFormFields(Application $application, Request $request): array
    {
        $call = $application->call;
        if ($call === null) {
            return [];
        }

        $langHeader = strtolower((string) $request->header('X-Locale', 'sk'));
        if (! in_array($langHeader, ['sk', 'en'], true)) {
            $langHeader = 'sk';
        }

        $language = Language::query()->where('name', $langHeader)->first()
            ?? Language::query()->where('name', 'sk')->first();

        $schema = CallFormSchema::build($call, $language, $langHeader);
        $fields = $schema['fields'] ?? [];

        return array_map(function ($field) {
            return [
                'name' => isset($field['name']) ? (string) $field['name'] : '',
                'label' => isset($field['label']) ? (string) $field['label'] : (isset($field['name']) ? (string) $field['name'] : ''),
                'type' => isset($field['type']) ? (string) $field['type'] : 'text',
                'placeholder' => isset($field['placeholder']) ? (string) $field['placeholder'] : null,
                'description' => isset($field['description']) ? (string) $field['description'] : null,
                'options' => $field['options'] ?? null,
            ];
        }, $fields);
    }


    private function applicationDetailPayload(Request $request, Application $application, ?Evaluation $currentEvaluation, Collection $commissionMembers, ?int $currentCommissionMemberId = null): array
    {
        $application->loadMissing([
            'call.program.typeOfProgram:id,name',
            'call.organization:id,name',
            'call.callCriteria.criterionTranslations:id,criterion_id,language_id,name',
            'team.members',
            'team.members.student.academicFlags',
            'team.members.student.academicRecord',
            'team.members.student.university',
            'team.members.student.studyProgram.studyProgramTranslations',
            'team.members.student.studyYear.studyYearTranslations',
            'documents.versions',
            'statusHistory.status:id,name',
            'statusHistory.changedBy:id,name,surname',
            'status:id,name',
            'category.categoryTranslations:id,category_id,language_id,name',
            'answers'
        ]);

        $callCriteria = $application->call?->callCriteria ?? collect();

        $evaluations = Evaluation::query()
            ->with('scores')
            ->where('application_id', $application->id)
            ->get();

        $totals = $evaluations->map(fn (Evaluation $evaluation) => $this->evaluationTotal($evaluation));
        $avgScore = $totals->isNotEmpty() ? $totals->avg() : null;

        $scoreMap = [];
        foreach ($evaluations as $evaluation) {
            $scoreMap[$evaluation->commission_member_id] = $this->evaluationTotal($evaluation);
        }

        $teamRoleMap = TeamRole::query()->pluck('name', 'id');

        return [
            'answers' => $application->answers->first()?->answer ?? [],
            'id' => $application->id,
            'status' => $this->normalizeApplicationStatus($application->status?->name),
            'academic_flag' => $this->resolveAcademicFlag($application),
            'team' => [
                'id' => $application->team?->id,
                'name' => $application->team?->name ?? '',
                'members_count' => $application->team?->members?->count() ?? 0,
            ],
            'my_score' => $currentEvaluation ? $this->evaluationTotal($currentEvaluation) : null,
            'submitted_at' => $application->submitted_at ? $application->submitted_at->format('d.m.Y') : '',
            'projectName' => $application->call?->name ?? $application->team?->name ?? ('Prihláška #'.$application->id),
            'teamName' => $application->team?->name ?? '',
            'program' => $this->normalizeProgramLabel($application->call?->program?->typeOfProgram?->name),
            'deadline' => $application->call?->application_deadline?->format('Y-m-d'),
            'avgScore' => $avgScore,
            'call_id' => $application->call_id,
            'category' => $application->category ? [
                'id' => $application->category->id,
                'name' => $application->category->categoryTranslations
                        ->firstWhere('language_id', LanguageType::SLOVAK->value)?->name
                    ?? $application->category->slug,
            ] : null,
            'description' => $application->call?->description ?? '',
            'form_data' => $application->form_data ?? [],
            'form_fields' => $this->resolveFormFields($application, $request),
            'documents' => $application->documents->map(function ($document) {
                $latest = $document->versions->sortByDesc('id')->first();

                return [
                    'id' => $document->id,
                    'type' => $latest?->file_name ?? ('Dokument #'.$document->id),
                    'version' => $latest?->id ?? 1,
                    'url' => route('documents.download', ['document' => $document->id]),
                ];
            })->values(),
            'history' => $application->statusHistory->map(function ($history) {
                return [
                    'status' => $this->normalizeApplicationStatus($history->status?->name),
                    'changed_at' => optional($history->created_at)?->toDateTimeString(),
                    'changed_by' => $history->changedBy ? trim(($history->changedBy->first_name ?? '').' '.($history->changedBy->last_name ?? '')) : 'Systém',
                    'note' => $history->note ?? '',
                ];
            })->values(),
            'status_history' => $application->statusHistory->map(function ($history) {
                return [
                    'status' => $this->normalizeApplicationStatus($history->status?->name),
                    'changed_at' => optional($history->created_at)?->toDateTimeString(),
                    'changed_by' => $history->changedBy ? trim(($history->changedBy->first_name ?? '').' '.($history->changedBy->last_name ?? '')) : 'Systém',
                    'note' => $history->note ?? '',
                ];
            })->values(),
            'evaluation' => $currentEvaluation ? $this->evaluationPayload($currentEvaluation, $callCriteria) : null,
            'call' => $application->call ? $this->callPayload($application->call, $currentCommissionMemberId) : null,
            'teamMembers' => $application->team?->members?->map(function ($member) use ($teamRoleMap) {
                    $student = $member->student;
                    $record = $student?->academicRecord;

                    return [
                        'id' => $member->id,
                        'student_id' => $student?->id,
                        'name' => trim(($member->name ?? '').' '.($member->surname ?? '')),
                        'role' => $teamRoleMap->get((int) $member->pivot->team_role_id, 'Člen tímu'),
                        'honor_declaration' => (bool) ($record?->honor_declaration ?? false),
                        'honor_declaration_signed_at' => optional($record?->honor_declaration_signed_at)?->toDateTimeString(),
                        'transcript_file' => $record?->transcript_file ? Storage::url($record->transcript_file) : null,
                        'school' => $student?->university?->name ?? null,
                        'study_program' => $student?->studyProgram?->studyProgramTranslations?->first()?->name ?? $student?->studyProgram?->name ?? null,
                        'study_year' => $student?->studyYear?->studyYearTranslations?->first()?->name ?? null,
                    ];
                })->values() ?? [],
            'commissionMembers' => $commissionMembers->filter(function (CommissionMember $commissionMember) use ($currentCommissionMemberId) {
                return $commissionMember->id === $currentCommissionMemberId;
            })->map(function (CommissionMember $commissionMember) use ($scoreMap) {
                $user = $commissionMember->user;

                return [
                    'id' => $commissionMember->id,
                    'name' => trim(($user?->name ?? '').' '.($user?->surname ?? '')),
                    'score' => array_key_exists($commissionMember->id, $scoreMap) ? $scoreMap[$commissionMember->id] : null,
                ];
            })->values(),
        ];
    }

    private function saveEvaluation(Request $request, int $applicationId, ?int $evaluationId = null): JsonResponse
    {
        $commissionMember = CommissionMember::query()
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $applicationState = Application::where('id', $applicationId)->firstOrFail();
        if($applicationState->active_status != 3){ // V Hodnotení
            abort(403, "Prihlášku už nemôžete hodnotiť !");
        }

        $validated = $request->validate([
            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*.name' => ['required', 'string'],
            'criteria.*.max_score' => ['required', 'numeric', 'min:0'],
            'criteria.*.score' => ['required', 'numeric', 'min:0'],
            'criteria.*.comment' => ['nullable', 'string'],
            'internal_note' => ['nullable', 'string'],
            'recommendation' => ['required', 'in:approve,reject,supplement'],
            'is_final' => ['required', 'boolean'], // Draft alebo finálne hodnotenie
        ]);

        $application = Application::query()
            ->with([
                'call.callCriteria.criterionTranslations:id,criterion_id,language_id,name',
                'status:id,name',
            ])
            ->findOrFail($applicationId);


        $existingEvaluation = Evaluation::query()
            ->where('application_id', $application->id)
            ->where('commission_member_id', $commissionMember->id)
            ->first();
        // ──────────────────────────────────────────────────────────────────────────

        if ($evaluationId === null) {
            $this->authorize('create', Evaluation::class);
        }

        $evaluation = null;
        if ($evaluationId !== null) {
            $evaluation = Evaluation::query()
                ->where('id', $evaluationId)
                ->where('application_id', $application->id)
                ->where('commission_member_id', $commissionMember->id)
                ->firstOrFail();

            $this->authorize('update', $evaluation);
        }

        $decisionId = $this->resolveDecisionId($validated['recommendation']);
        $isFinal = (bool) $validated['is_final'] && $validated['recommendation'] !== 'supplement';

        $evaluation = DB::transaction(function () use ($evaluation, $validated, $application, $commissionMember, $decisionId, $evaluationId, $isFinal) {
            if ($evaluationId !== null) {

                $evaluation->update([
                    'decision_id' => $decisionId,
                    'internal_note' => $validated['internal_note'] ?? null,
                    'submitted_at' => $isFinal ? now() : null,
                    'locked' => $isFinal,
                ]);
            } else {
                // Vytvorenie úplne nového záznamu
                $evaluation = Evaluation::query()->create([
                    'application_id' => $application->id,
                    'commission_member_id' => $commissionMember->id,
                    'decision_id' => $decisionId,
                    'internal_note' => $validated['internal_note'] ?? null,
                    'submitted_at' => $isFinal ? now() : null,
                    'locked' => $isFinal,
                ]);
            }

            // Premazanie starých bodov a uloženie nových
            EvaluationScore::query()->where('evaluation_id', $evaluation->id)->delete();

            foreach ($validated['criteria'] as $criterion) {
                $criterionId = Criterion::query()
                    ->whereHas('criterionTranslations', function ($query) use ($criterion) {
                        $query->where('name', $criterion['name']);
                    })
                    ->value('id');

                if ($criterionId === null) {
                    continue;
                }

                EvaluationScore::query()->create([
                    'evaluation_id' => $evaluation->id,
                    'criterion_id' => $criterionId,
                    'score' => $criterion['score'],
                    'comment' => $criterion['comment'] ?? '',
                ]);
            }

            return $evaluation->load('scores', 'decision');
        });

        return response()->json([
            'message' => $isFinal ? 'Hodnotenie bolo úspešne odoslané.' : 'Koncept bol úspešne uložený.',
            'evaluation' => $this->evaluationPayload($evaluation, $application->call?->callCriteria ?? collect()),
        ], $evaluationId === null ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    public function pending(Request $request): JsonResponse
    {
        $user = $request->user();

        $commissionMemberIds = CommissionMember::query()
            ->where('user_id', $user->id)
            ->pluck('id');

        if ($commissionMemberIds->isEmpty()) {
            return response()->json([
                'data' => [],
            ]);
        }

        $perPage = (int) $request->query('per_page', 15);
        if ($perPage < 1) {
            $perPage = 15;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $applications = Application::query()
            ->select([
                'application.id',
                'application.call_id',
                'application.team_id',
                'application.created_by',
                'application.submitted_at',
                'application.created_at',
            ])
            ->with([
                'call:id,name',
                'creator:id,name,surname,email',
            ])
            ->whereNotNull('application.submitted_at')
            ->whereNotExists(function ($query) use ($commissionMemberIds) {
                $query->selectRaw('1')
                    ->from('evaluation')
                    ->whereColumn('evaluation.application_id', 'application.id')
                    ->whereIn('evaluation.commission_member_id', $commissionMemberIds);
            })
            ->latest('application.id')
            ->paginate($perPage);

        return response()->json($applications);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $commissionMemberIds = $this->resolveCommissionMemberIds($request);
        $currentCommissionMemberId = $commissionMemberIds->first();

        if ($currentCommissionMemberId === null) {
            return response()->json([
                'stats' => ['total' => 0, 'pending' => 0, 'evaluated' => 0, 'decided' => 0],
                'calls' => [],
                'applications' => [],
                'recentApplications' => [],
                'urgentApplications' => [],
            ]);
        }

        $assignedApplicationIds = Evaluation::whereIn('commission_member_id', $commissionMemberIds)
            ->pluck('application_id')
            ->unique();

        $applications = Application::query()
            ->with(['call.program.typeOfProgram:id,name', 'team.members', 'status:id,name'])
            ->whereIn('id', $assignedApplicationIds)
            ->whereNotNull('submitted_at')
            ->latest('id')
            ->get();

        $evaluations = Evaluation::query()
            ->with('scores')
            ->whereIn('application_id', $assignedApplicationIds)
            ->get();

        $myDecisions = $evaluations->where('commission_member_id', $currentCommissionMemberId)
            ->whereNotNull('submitted_at')
            ->whereNotNull('decision_id');

        $myDecidedApplicationIds = $myDecisions->pluck('application_id');

        $applicationMetrics = [];
        foreach ($evaluations as $evaluation) {
            $total = $this->evaluationTotal($evaluation);
            $applicationMetrics[$evaluation->application_id]['totals'][] = $total;

            if ((int) $evaluation->commission_member_id === $currentCommissionMemberId) {
                $applicationMetrics[$evaluation->application_id]['my_score'] = $total;
                $applicationMetrics[$evaluation->application_id]['my_decision'] = $evaluation->decision_id;
            }
        }

        $summaries = $applications->map(function (Application $application) use ($applicationMetrics, $currentCommissionMemberId) {
            $summary = $this->applicationSummary($application, $currentCommissionMemberId);
            $summary['has_decision'] = isset($applicationMetrics[$application->id]['my_decision']);
            return $summary;
        })->values();

        $assignedCallIds = $applications->pluck('call_id')->unique()->filter();

        $calls = ProgramCall::query()
            ->withCount('applications')
            ->with(['program.typeOfProgram:id,name', 'currentStatusHistory.status:id,name', 'callCriteria.criterionTranslations:id,criterion_id,language_id,name', 'applications.team.members', 'applications.status:id,name'])
            ->whereIn('id', $assignedCallIds)
            ->get()
            ->map(fn (ProgramCall $call) => $this->callPayload($call, $currentCommissionMemberId))
            ->values();

        $stats = [
            'total' => $summaries->count(),
            'pending' => $summaries->filter(fn (array $summary) =>
                !$myDecidedApplicationIds->contains($summary['id']) &&
                in_array($summary['status'], ['submitted', 'under_review', 'evaluating', 'supplement'], true)
            )->count(),
            'evaluated' => $summaries->filter(fn (array $summary) => isset($summary['my_score']) && $summary['my_score'] !== null)->count(),
            'decided' => $myDecidedApplicationIds->count(),
        ];

        return response()->json([
            'stats' => $stats,
            'calls' => $calls,
            'applications' => $summaries,
            'recentApplications' => $summaries->take(3)->values(),
            'urgentApplications' => $summaries->filter(fn (array $summary) =>
                !$myDecidedApplicationIds->contains($summary['id']) &&
                in_array($summary['status'], ['submitted', 'under_review', 'evaluating'], true)
            )->values(),
        ]);
    }
    /*
    public function calls(Request $request): JsonResponse
    {
        $commissionMemberIds = $this->resolveCommissionMemberIds($request);
        $currentCommissionMemberId = $commissionMemberIds->first();

        $callQuery = ProgramCall::query()
            ->withCount('applications')
            ->with([
                'program.typeOfProgram:id,name',
                'currentStatusHistory.status:id,name',
                'callCriteria.criterionTranslations:id,criterion_id,language_id,name',
                'applications.status:id,name',
            ]);

        if ($this->isOrgMember($request)) {
            $callQuery->whereIn('id', $this->orgMemberCallIds($request));
        } else {
            $callQuery->whereHas('currentStatusHistory.status', function ($query) {
                $query->where('name', 'Publikované');
            });
        }

        $calls = $callQuery->latest('id')
            ->get()
            ->map(fn (ProgramCall $call) => $this->callPayload($call, $currentCommissionMemberId))
            ->values();

        return response()->json($calls);
    }
    */

    /*
    public function callApplications(Request $request, int $callId): JsonResponse
    {
        $commissionMemberIds = $this->resolveCommissionMemberIds($request);
        $currentCommissionMemberId = $commissionMemberIds->first();

        $applications = Application::query()
            ->with([
                'call.program.typeOfProgram:id,name',
                'team.members',
                'status:id,name',
            ])
            ->where('call_id', $callId)
            ->whereNotNull('submitted_at')
            ->latest('id')
            ->get()
            ->map(fn (Application $application) => $this->applicationSummary($application, $currentCommissionMemberId))
            ->values();

        return response()->json($applications);
    }
    */

    public function application(Request $request, int $applicationId): JsonResponse
    {
        $application = Application::query()
            ->with([
                'call.program.typeOfProgram:id,name',
                'call.organization:id,name',
                'call.callCriteria.criterionTranslations:id,criterion_id,language_id,name',
                'team.members',
                'documents.versions',
                'statusHistory.status:id,name',
                'status:id,name',
            ])
            ->findOrFail($applicationId);

        $currentCommissionMember = CommissionMember::query()
            ->where('user_id', $request->user()->id)
            ->first();

        $currentEvaluation = $currentCommissionMember
            ? Evaluation::query()
                ->with('scores', 'decision')
                ->where('application_id', $application->id)
                ->where('commission_member_id', $currentCommissionMember->id)
                ->first()
            : null;

        $commissionMembers = CommissionMember::query()
            ->with('user')
            ->where(function ($q) use ($application) {
                $q->whereNull('call_id')
                  ->orWhere('call_id', $application->call_id);
            })
            ->orderBy('id')
            ->get();

        return response()->json($this->applicationDetailPayload($request, $application, $currentEvaluation, $commissionMembers, $currentCommissionMember?->id));
    }

    public function index(Request $request, int $applicationId): JsonResponse
    {
        $application = Application::query()
            ->with(['call.callCriteria.criterionTranslations:id,criterion_id,language_id,name'])
            ->findOrFail($applicationId);

        $this->authorize('viewAny', Evaluation::class);

        $evaluations = Evaluation::query()
            ->with(['scores', 'commissionMember.user', 'decision'])
            ->where('application_id', $applicationId)
            ->get();

        $items = $evaluations->map(function (Evaluation $evaluation) use ($application) {
            return [
                'id' => $evaluation->id,
                'commission_member_id' => $evaluation->commission_member_id,
                'evaluator' => [
                    'id' => $evaluation->commissionMember?->user?->id,
                    'name' => trim(($evaluation->commissionMember?->user?->name ?? '') . ' ' . ($evaluation->commissionMember?->user?->surname ?? '')),
                ],
                'submitted_at' => optional($evaluation->submitted_at ?? $evaluation->created_at)?->toDateTimeString(),
                'criteria' => $this->evaluationPayload($evaluation, $application->call?->callCriteria ?? collect())['criteria'] ?? [],
                'total_score' => $this->evaluationTotal($evaluation),
                'recommendation' => $this->normalizeApplicationStatus($evaluation->decision?->name) === 'approved'
                    ? 'approve'
                    : ($this->normalizeApplicationStatus($evaluation->decision?->name) === 'rejected' ? 'reject' : 'supplement'),
            ];
        });

        return response()->json(['evaluations' => $items]);
    }

    public function storeEvaluatorEvaluation(Request $request, int $applicationId): JsonResponse
    {
        return $this->saveEvaluation($request, $applicationId);
    }

    public function updateEvaluatorEvaluation(Request $request, int $applicationId, int $evaluationId): JsonResponse
    {
        return $this->saveEvaluation($request, $applicationId, $evaluationId);
    }

    public function requestSupplement(Request $request, int $applicationId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3'],
        ]);

        $application = Application::query()->findOrFail($applicationId);
        $this->workflowService()->requestSupplement($application, $validated['reason'], $request->user());

        return response()->json([
            'message' => 'Žiadosť o doplnenie bola odoslaná.',
        ]);
    }

    public function storeScore(Request $request, int $applicationId)
    {
        $this->authorize('create', EvaluationScore::class);

        $validated = $request->validate([
            'decision_id'          => ['required', 'integer', 'exists:decision,id'],
            'scores'               => ['required', 'array', 'min:1'],
            'scores.*.criterion_id' => ['required', 'integer', 'exists:criterion,id'],
            'scores.*.score'       => ['required', 'numeric', 'min:0'],
            'scores.*.comment'     => ['required', 'string'],
        ]);

        $commissionMember = CommissionMember::where('user_id', $request->user()->id)->firstOrFail();

        $existingEvaluation = Evaluation::where('application_id', $applicationId)
            ->where('commission_member_id', $commissionMember->id)
            ->first();

        if ($existingEvaluation) {
            return response()->json([
                'message' => 'Táto prihláška už bola hodnotená.',
            ], Response::HTTP_CONFLICT);
        }

        $evaluation = DB::transaction(function () use ($validated, $applicationId, $commissionMember) {
            $evaluation = Evaluation::create([
                'application_id'       => $applicationId,
                'commission_member_id' => $commissionMember->id,
                'decision_id'          => $validated['decision_id'],
            ]);

            foreach ($validated['scores'] as $score) {
                EvaluationScore::create([
                    'evaluation_id' => $evaluation->id,
                    'criterion_id'  => $score['criterion_id'],
                    'score'         => $score['score'],
                    'comment'       => $score['comment'],
                ]);
            }

            return $evaluation;
        });

        $this->workflowService()->notifyEvaluatorAssigned($evaluation);

        return response()->json([
            'message'    => 'Hodnotenie bolo úspešne odoslané.',
            'evaluation' => $evaluation->load('scores'),
        ], Response::HTTP_CREATED);
    }
}
