<?php

namespace Modules\Evaluation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\ApplicationWorkflowService;
use Modules\Applications\Models\Application;
use Modules\Evaluation\Models\CommissionMember;
use Modules\Evaluation\Models\Decision;
use Modules\Evaluation\Models\Evaluation;
use Modules\Evaluation\Models\EvaluationScore;
use Modules\Content\Enums\LanguageType;
use Modules\Programs\Models\Call as ProgramCall;
use Modules\Programs\Models\Criterion;
use Modules\Teams\Models\TeamRole;

class EvaluationController extends Controller
{
    use AuthorizesRequests;

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
            'recommendation' => $this->normalizeApplicationStatus($evaluation->decision?->name) === 'approved'
                ? 'approve'
                : ($this->normalizeApplicationStatus($evaluation->decision?->name) === 'rejected' ? 'reject' : 'supplement'),
            'internal_note' => null,
            'submitted_at' => optional($evaluation->submitted_at ?? $evaluation->created_at)?->toDateTimeString(),
            'locked' => false,
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

    private function applicationDetailPayload(Application $application, ?Evaluation $currentEvaluation, Collection $commissionMembers, ?int $currentCommissionMemberId = null): array
    {
        $application->loadMissing([
            'call.program.typeOfProgram:id,name',
            'call.organization:id,name',
            'call.callCriteria.criterionTranslations:id,criterion_id,language_id,name',
            'team.members',
            'team.members.student.academicFlags',
            'documents.versions',
            'statusHistory.status:id,name',
            'status:id,name',
            'category.categoryTranslations:id,category_id,language_id,name',
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
                    'changed_by' => $history->note ?? '',
                ];
            })->values(),
            'evaluation' => $currentEvaluation ? $this->evaluationPayload($currentEvaluation, $callCriteria) : null,
            'call' => $application->call ? $this->callPayload($application->call, $currentCommissionMemberId) : null,
            'teamMembers' => $application->team?->members?->map(function ($member) use ($teamRoleMap) {
                return [
                    'id' => $member->id,
                    'name' => trim(($member->name ?? '').' '.($member->surname ?? '')),
                    'role' => $teamRoleMap->get((int) $member->pivot->team_role_id, 'Člen tímu'),
                ];
            })->values() ?? [],
            'commissionMembers' => $commissionMembers->map(function (CommissionMember $commissionMember) use ($scoreMap) {
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

        $validated = $request->validate([
            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*.name' => ['required', 'string'],
            'criteria.*.max_score' => ['required', 'numeric', 'min:0'],
            'criteria.*.score' => ['required', 'numeric', 'min:0'],
            'criteria.*.comment' => ['nullable', 'string'],
            'internal_note' => ['nullable', 'string'],
            'recommendation' => ['required', 'in:approve,reject,supplement'],
        ]);

        $application = Application::query()
            ->with([
                'call.callCriteria.criterionTranslations:id,criterion_id,language_id,name',
                'status:id,name',
            ])
            ->findOrFail($applicationId);

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

        $existingEvaluation = Evaluation::query()
            ->where('application_id', $application->id)
            ->where('commission_member_id', $commissionMember->id)
            ->first();

        if ($evaluationId === null && $existingEvaluation !== null) {
            return response()->json([
                'message' => 'Hodnotenie pre túto prihlášku ste už odoslali.',
            ], Response::HTTP_CONFLICT);
        }

        $decisionId = $this->resolveDecisionId($validated['recommendation']);
        $statusName = match ($validated['recommendation']) {
            'approve' => 'Schválené',
            'reject' => 'Zamietnuté',
            'supplement' => 'Vyžiadané doplnenie',
            default => 'Vyžiadané doplnenie',
        };

        $evaluation = DB::transaction(function () use ($validated, $application, $commissionMember, $decisionId, $evaluationId, $statusName) {
            $evaluation = $evaluationId !== null
                ? $evaluation
                : Evaluation::query()->create([
                    'application_id' => $application->id,
                    'commission_member_id' => $commissionMember->id,
                    'decision_id' => $decisionId,
                    'submitted_at' => now(),
                ]);

            $evaluation->update([
                'decision_id' => $decisionId,
            ]);

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
            'message' => 'Hodnotenie bolo úspešne uložené.',
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

        $applications = Application::query()
            ->with([
                'call.program.typeOfProgram:id,name',
                'team.members',
                'status:id,name',
            ])
            ->whereNotNull('submitted_at')
            ->latest('id')
            ->get();

        if ($applications->isEmpty()) {
            return response()->json([
                'stats' => [
                    'total' => 0,
                    'pending' => 0,
                    'evaluated' => 0,
                    'decided' => 0,
                ],
                'calls' => [],
                'applications' => [],
                'recentApplications' => [],
                'urgentApplications' => [],
            ]);
        }

        $evaluations = Evaluation::query()
            ->with('scores')
            ->whereIn('application_id', $applications->pluck('id'))
            ->get();

        $applicationMetrics = [];
        foreach ($evaluations as $evaluation) {
            $total = $this->evaluationTotal($evaluation);
            $applicationMetrics[$evaluation->application_id]['totals'][] = $total;

            if ($currentCommissionMemberId !== null && (int) $evaluation->commission_member_id === $currentCommissionMemberId) {
                $applicationMetrics[$evaluation->application_id]['my_score'] = $total;
            }
        }

        $summaries = $applications->map(function (Application $application) use ($applicationMetrics, $currentCommissionMemberId) {
            return $this->applicationSummary($application, $currentCommissionMemberId);
        })->values();

        $calls = ProgramCall::query()
            ->withCount('applications')
            ->with([
                'program.typeOfProgram:id,name',
                'currentStatusHistory.status:id,name',
                'callCriteria.criterionTranslations:id,criterion_id,language_id,name',
                'applications.team.members',
                'applications.status:id,name',
            ])
            ->whereHas('currentStatusHistory.status', function ($query) {
                $query->where('name', 'Publikované');
            })
            ->latest('id')
            ->get()
            ->map(fn (ProgramCall $call) => $this->callPayload($call, $currentCommissionMemberId))
            ->values();

        $stats = [
            'total' => $summaries->count(),
            'pending' => $summaries->filter(fn (array $summary) => $summary['my_score'] === null && in_array($summary['status'], ['submitted', 'evaluating', 'supplement'], true))->count(),
            'evaluated' => $summaries->filter(fn (array $summary) => $summary['my_score'] !== null)->count(),
            'decided' => $summaries->filter(fn (array $summary) => in_array($summary['status'], ['approved', 'rejected'], true))->count(),
        ];

        return response()->json([
            'stats' => $stats,
            'calls' => $calls,
            'applications' => $summaries,
            'recentApplications' => $summaries->take(3)->values(),
            'urgentApplications' => $summaries->filter(fn (array $summary) => $summary['my_score'] === null && in_array($summary['status'], ['submitted', 'evaluating'], true))->values(),
        ]);
    }

    public function calls(Request $request): JsonResponse
    {
        $commissionMemberIds = $this->resolveCommissionMemberIds($request);
        $currentCommissionMemberId = $commissionMemberIds->first();

        $calls = ProgramCall::query()
            ->withCount('applications')
            ->with([
                'program.typeOfProgram:id,name',
                'currentStatusHistory.status:id,name',
                'callCriteria.criterionTranslations:id,criterion_id,language_id,name',
                'applications.status:id,name',
            ])
            ->whereHas('currentStatusHistory.status', function ($query) {
                $query->where('name', 'Publikované');
            })
            ->latest('id')
            ->get()
            ->map(fn (ProgramCall $call) => $this->callPayload($call, $currentCommissionMemberId))
            ->values();

        return response()->json($calls);
    }

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

    public function application(Request $request, int $applicationId): JsonResponse
    {
        $commissionMembers = CommissionMember::query()
            ->with('user')
            ->orderBy('id')
            ->get();

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

        return response()->json($this->applicationDetailPayload($application, $currentEvaluation, $commissionMembers, $currentCommissionMember?->id));
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
