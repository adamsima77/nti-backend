<?php

namespace Modules\Mentorship\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Applications\Models\Application;
use Modules\Content\Enums\LanguageType;
use Modules\IdentityAccess\Models\User;
use Modules\Mentorship\Events\MentorSessionEvent;
use Modules\Mentorship\Events\MilestoneStatusChanged;
use Modules\Mentorship\Models\MentorshipSession;
use Modules\Mentorship\Models\Milestone;
use Modules\Mentorship\Models\Mentorship;
use Modules\Teams\Models\Team;

class MentorshipController extends Controller
{
    use AuthorizesRequests;
    public function assignMentor(Request $request, Application $application, User $user)
    {
        $this->authorize('assignMentor', Mentorship::class);

        // Zápis výhradne do prepájacej tabuľky mentorship
        Mentorship::firstOrCreate([
            'mentor_user_id' => $user->id,
            'application_id' => $application->id,
        ]);

        return response()->json(['message' => 'Mentor assigned successfully.'], Response::HTTP_OK);
    }
    public function fetchMentors(Request $request)
    {
        $mentors = User::whereHas('roles', function ($query) {
            $query->where('name', 'mentor');
        })->get();

        return response()->json(['mentors' => $mentors], Response::HTTP_OK);
    }
    public function dashboard(Request $request): JsonResponse
    {
        $mentor = $this->currentMentor($request);
        $projects = $this->mentorProjects($mentor->id, $request);

        return response()->json([
            'stats' => [
                'totalProjects' => $projects->count(),
                'activeProjects' => $projects->where('status', 'active')->count(),

                'pendingMilestones' => $projects->where('pendingMilestone', true)->count(),
                'consultationsThisMonth' => $projects->sum('consultationsCount'),
            ],

            'projects' => $projects->take(5)->values(),

            'pendingActions' => $projects
                ->filter(fn (array $project) => $project['pendingMilestone'] === true)
                ->take(5)
                ->values()
                ->map(fn (array $project) => [
                    'id' => 'project-'.$project['id'],
                    'message' => sprintf('Projekt %s čaká na schválenie míľnika.', $project['name']),
                    'link' => '/mentor/projekty/'.$project['id'],
                ]),
            'recentConsultations' => collect($this->recentConsultations($mentor->id))->take(5)->values(),
        ]);
    }

    public function projects(Request $request): JsonResponse
    {
        $mentor = $this->currentMentor($request);

        return response()->json($this->mentorProjects($mentor->id, $request));
    }

    public function consultations(Request $request): JsonResponse
    {
        $mentor = $this->currentMentor($request);

        return response()->json($this->allConsultations($mentor->id, $request));
    }

    public function projectMilestones(Request $request, int $project): JsonResponse
    {
        $mentor = $this->currentMentor($request);
        $application = $this->mentorApplicationOrFail($mentor->id, $project);

        return response()->json(
            $application->milestones
                ->map(fn (Milestone $milestone) => $this->formatMilestone($milestone))
                ->values()
        );
    }

    public function updateMilestone(Request $request, int $project, int $milestone): JsonResponse
    {
        $mentor = $this->currentMentor($request);
        $application = $this->mentorApplicationOrFail($mentor->id, $project);
        $milestoneModel = $application->milestones()->whereKey($milestone)->firstOrFail();

        $currentStatus = $this->normalizeMilestoneStatus($milestoneModel->status);

        if ($currentStatus !== 'pending_approval') {
            return response()->json([
                'message' => 'Tento míľnik momentálne nie je pripravený na schválenie mentora.',
            ], 422);
        }

        if (! $this->previousMilestonesCompleted($application, $milestoneModel)) {
            return response()->json([
                'message' => 'Najprv musíte schváliť predchádzajúce míľniky.',
            ], 422);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:completed,approved,rejected'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        $targetStatus = in_array($validated['status'], ['completed', 'approved'], true)
            ? 'completed'
            : 'rejected';

        if ($targetStatus === 'rejected') {
            $request->validate([
                'comment' => ['required', 'string', 'min:20'],
            ]);
        }

        $oldStatus = $milestoneModel->status;

        $milestoneModel->update([
            'status' => $targetStatus,
        ]);

        if ($targetStatus === 'rejected' && filled($request->input('comment'))) {
            $this->storeMilestoneComment($milestoneModel, $mentor->id, (string) $request->input('comment'));
        }

        $freshMilestone = $milestoneModel->fresh();
        $freshMilestone?->load(['application.team.members', 'application.creator', 'application.call']);

        event(new MilestoneStatusChanged(
            $freshMilestone,
            $oldStatus,
            $targetStatus,
            $mentor,
            $this->resolveLanguageId($request),
        ));

        return response()->json($this->formatMilestone($freshMilestone));
    }

    public function projectConsultations(Request $request, int $project): JsonResponse
    {
        $mentor = $this->currentMentor($request);

        $this->mentorApplicationOrFail($mentor->id, $project);

        if ($request->isMethod('post')) {

            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'type' => ['required', 'in:online,offline'],
                'scheduled_at' => ['required', 'date'],
                'meeting_url' => ['nullable', 'url'],
                'agenda' => ['nullable', 'string', 'max:5000'],
                'duration' => ['required', 'integer', 'min:1'],
            ]);

            if (
                $validated['type'] === 'online'
                && empty($validated['meeting_url'])
            ) {
                return response()->json([
                    'message' => 'Pre online stretnutie je potrebné zadať odkaz.',
                ], 422);
            }

            $mentorshipId = $this->mentorshipId(
                $mentor->id,
                $project
            );

            $mentorSession = MentorshipSession::create([
                'mentorship_id' => $mentorshipId,
                'created_by' => $mentor->id,
                'title' => $validated['title'],
                'type' => $validated['type'],
                'meeting_url' => $validated['meeting_url'] ?? null,
                'scheduled_at' => $validated['scheduled_at'],
                'agenda' => $validated['agenda'] ?? null,
                'duration' => $validated['duration'],
                'status' => 'scheduled'
            ]);

            event(new MentorSessionEvent($mentorSession));

            return response()->json([
                'message' => 'Mentoringové sedenie bolo naplánované.',
            ], 201);
        }

        return response()->json(
            $this->consultationsForProject(
                $mentor->id,
                $project
            )
        );
    }

    public function storeConsultation(Request $request, int $project): JsonResponse
    {
        return $this->projectConsultations($request, $project);
    }

    public function storeFeedback(Request $request, int $project): JsonResponse
    {
        $mentor = $this->currentMentor($request);
        $this->mentorApplicationOrFail($mentor->id, $project);

        $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'recommendation' => ['nullable', 'string', 'max:5000'],
        ]);

        return response()->json([
            'message' => 'Feedback bol prijatý.',
        ]);
    }

    private function currentMentor(Request $request): User
    {
        $user = $request->user();

        abort_if($user === null, 401);
        abort_if(! $user->isMentor() && ! $user->isAdmin() && ! $user->isSuperAdmin(), 403);

        return $user;
    }

    private function mentorCalls(int $mentorId)
    {

        $calls = DB::table('mentorship')
            ->where('mentor_user_id', $mentorId)
            ->orderByDesc('created_at')
            ->get();

        $applicationIds = collect($calls->items())->pluck('application_id')->unique();

        $applications = Application::query()
            ->with([
                'creator:id,name,surname,email',
                'team.members',
                'call.program.typeOfProgram',
                'milestones',
                'status:id,name',
            ])
            ->whereIn('id', $applicationIds)
            ->get()
            ->keyBy('id');


        $transformedItems = collect($calls->items())->map(function ($assignment) use ($applications, $mentorId) {
            $application = $applications->get($assignment->application_id);

            if ($application === null) {
                return null;
            }

            $milestones = $application->milestones->map(fn (Milestone $milestone) => $this->formatMilestone($milestone))->values();
            $completed = $application->milestones->filter(fn (Milestone $milestone) => $this->isMilestoneCompleted($milestone->status))->count();
            $nextMilestone = $application->milestones
                ->first(fn (Milestone $milestone) => ! $this->isMilestoneCompleted($milestone->status));
            $hasPendingApproval = $application->milestones->contains(
                fn (Milestone $milestone) => $this->normalizeMilestoneStatus($milestone->status) === 'pending_approval'
            );

            return [
                'id' => $application->id,
                'name' => $application->call?->name ?? ('Projekt #'.$application->id),
                'teamName' => $application->team?->name,
                'program' => $application->call?->program?->typeOfProgram?->name ?? $application->call?->program?->name,
                'status' => $this->projectStatus($application),
                'assignedAt' => Carbon::parse($assignment->created_at)->format('d.m.Y'),
                'productOwner' => $application->creator ? [
                    'name' => trim(($application->creator->name ?? '').' '.($application->creator->surname ?? '')) ?: ($application->creator->email ?? 'Mentor'),
                    'email' => $application->creator->email,
                ] : null,
                'teamMembers' => $application->team
                    ? $application->team->members->map(function (User $member) {
                        return [
                            'id' => $member->id,
                            'name' => trim(($member->name ?? '').' '.($member->surname ?? '')) ?: ($member->email ?? ('Používateľ #'.$member->id)),
                            'role' => 'Člen tímu',
                        ];
                    })->values()
                    : [],
                'teamSize' => $application->team
                    ? ($application->team->relationLoaded('members')
                        ? $application->team->members->count()
                        : $application->team->members()->count())
                    : 0,
                'consultationsCount' => $this->consultationsCount($mentorId, (int) $application->id),
                'milestonesCompleted' => $completed,
                'milestonesTotal' => $application->milestones->count(),
                'nextMilestone' => $nextMilestone?->name,
                'pendingMilestone' => $hasPendingApproval,
                'milestones' => $milestones,
            ];
        })->filter()->values();

        return $calls->setCollection($transformedItems);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */

    private function mentorProjects(int $mentorId, Request $request)
    {
        $query = Application::query()
            ->with([
                'creator:id,name,surname,email',
                'team.members',
                'call.program.typeOfProgram',
                'milestones',
                'status:id,name',
                'mentorships',
            ])
            ->whereHas('mentorships', function ($q) use ($mentorId) {
                $q->where('mentor_user_id', $mentorId);
            });

        // =========================
        // FILTER: STATUS
        // =========================
        if ($request->filled('status')) {
            $query->where('active_status', $request->status);
            }

        // =========================
        // FILTER: PROGRAM
        // =========================
        if ($request->filled('program')) {
            $query->whereHas('call', function ($q) use ($request) {
                $q->where('program_id', $request->program);
            });
        }

        $paginator = $query->orderByDesc('created_by')
            ->paginate(30);

        $transformed = collect($paginator->items())->map(function (Application $application) use ($mentorId) {

            $milestones = $application->milestones
                ->map(fn ($milestone) => $this->formatMilestone($milestone))
                ->values();

            $completed = $application->milestones
                ->filter(fn ($milestone) => $this->isMilestoneCompleted($milestone->status))
                ->count();

            $nextMilestone = $application->milestones
                ->first(fn ($milestone) => ! $this->isMilestoneCompleted($milestone->status));

            $hasPendingApproval = $application->milestones->contains(
                fn ($milestone) =>
                    $this->normalizeMilestoneStatus($milestone->status) === 'pending_approval'
            );

            return [
                'id' => $application->id,
                'name' => $application->call?->name ?? ('Projekt #' . $application->id),
                'teamName' => $application->team?->name,
                'program' => $application->call?->program?->typeOfProgram?->name
                    ?? $application->call?->program?->name,

                'status' => $this->projectStatus($application),

                'assignedAt' => $application->mentorships
                    ->firstWhere('mentor_user_id', $mentorId)
                    ?->created_at
                    ?->format('d.m.Y'),

                'productOwner' => $application->creator ? [
                    'name' => trim(($application->creator->name ?? '') . ' ' . ($application->creator->surname ?? ''))
                        ?: ($application->creator->email ?? 'Mentor'),
                    'email' => $application->creator->email,
                ] : null,

                'teamMembers' => $application->team
                    ? $application->team->members->map(function ($member) {
                        return [
                            'id' => $member->id,
                            'name' => trim(($member->name ?? '') . ' ' . ($member->surname ?? ''))
                                ?: ($member->email ?? ('Používateľ #' . $member->id)),
                            'role' => 'Člen tímu',
                        ];
                    })->values()
                    : [],

                'teamSize' => $application->team
                    ? $application->team->members->count()
                    : 0,

                'consultationsCount' =>
                    $this->consultationsCount($mentorId, (int) $application->id),

                'milestonesCompleted' => $completed,
                'milestonesTotal' => $application->milestones->count(),
                'nextMilestone' => $nextMilestone?->name,
                'pendingMilestone' => $hasPendingApproval,
                'milestones' => $milestones,
            ];
        })->values();

        $paginator->setCollection($transformed);

        return $paginator;
    }

    private function mentorApplicationOrFail(int $mentorId, int $projectId): Application
    {
        $application = Application::query()
            ->with(['creator:id,name,surname,email', 'team.members', 'call.program.typeOfProgram', 'milestones', 'status:id,name'])
            ->whereKey($projectId)
            ->whereHas('mentorships', fn ($query) => $query->where('mentor_user_id', $mentorId))
            ->first();

        abort_if($application === null, 404);

        return $application;
    }




    private function mentorshipId(int $mentorId, int $projectId): int
    {
        $mentorshipId = DB::table('mentorship')
            ->where('mentor_user_id', $mentorId)
            ->where('application_id', $projectId)
            ->value('id');

        abort_if($mentorshipId === null, 404);

        return (int) $mentorshipId;
    }

    private function consultationsCount(int $mentorId, int $projectId): int
    {
        return DB::table('mentorship_session as ms')
            ->join('mentorship as m', 'm.id', '=', 'ms.mentorship_id')
            ->where('m.mentor_user_id', $mentorId)
            ->where('m.application_id', $projectId)
            ->count();
    }

    private function recentConsultations(int $mentorId)
    {
        return DB::table('mentorship_session as ms')
            ->join('mentorship as m', 'm.id', '=', 'ms.mentorship_id')
            ->join('application as a', 'a.id', '=', 'm.application_id')
            ->leftJoin('call as c', 'c.id', '=', 'a.call_id')
            ->orderByDesc('ms.scheduled_at')
            ->where('m.mentor_user_id', $mentorId)
            ->limit(5)
            ->get([
                'ms.id',
                'ms.scheduled_at',

                'a.id as project_id',
                'c.name as project_name',
            ])
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'projectId' => (int) $row->project_id,
                    'projectName' => $row->project_name ?? ('Projekt #'.$row->project_id),
                    'date' => Carbon::parse($row->scheduled_at)->format('d.m.Y'),
                ];
            });
    }

    private function consultationsForProject(int $mentorId, int $projectId)
    {
        return DB::table('mentorship_session as ms')
            ->join('mentorship as m', 'm.id', '=', 'ms.mentorship_id')
            ->where('m.mentor_user_id', $mentorId)
            ->where('m.application_id', $projectId)
            ->orderByDesc('ms.scheduled_at')
            ->get([
                'ms.id',
                'ms.scheduled_at',
                'ms.agenda',
                'ms.type',
            ])
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'title' => 'Konzultácia',
                    'date' => Carbon::parse($row->scheduled_at)->format('d.m.Y'),
                    'duration' => 60,
                    'type' => $row->type,
                    'summary' => mb_strimwidth(trim(strip_tags((string) $row->agenda)), 0, 200, '…'),
                    'actionItems' => [],
                ];
            });
    }

    private function allConsultations(int $mentorId, Request $request)
    {
        // 1. Vytvoríme základný query builder
        $query = DB::table('mentorship_session as ms')
            ->join('mentorship as m', 'm.id', '=', 'ms.mentorship_id')
            ->join('application as a', 'a.id', '=', 'm.application_id')
            ->leftJoin('call as c', 'c.id', '=', 'a.call_id')
            ->where('m.mentor_user_id', $mentorId);

        // 2. SERVEROVÉ FILTROVANIE (Aplikuje sa iba, ak frontend filter odoslal)

        // Filter na konkrétny projekt
        if ($request->filled('project_id')) {
            $query->where('a.id', $request->input('project_id'));
        }

        // Filter na typ konzultácie (online / offline)
        if ($request->filled('type')) {
            $query->where('ms.type', $request->input('type'));
        }

        // Filter na konkrétny mesiac (Frontend posiela formát "YYYY-MM", napr. "2026-06")
        if ($request->filled('month')) {
            $month = $request->input('month'); // očakáva napr. "2026-06"

            // Pre PostgreSQL (keďže používaš pgsql) filtrujeme bezpečne pomocou to_char alebo splitu
            $query->whereRaw("to_char(ms.scheduled_at, 'YYYY-MM') = ?", [$month]);
        }

        // 3. Zoradíme a spustíme pagináciu s aplikovanými WHERE podmienkami
        $paginator = $query->orderByDesc('ms.scheduled_at')
            ->paginate(30, [
                'ms.id',
                'ms.scheduled_at',
                'ms.agenda',
                'ms.type',
                'ms.duration',
                'a.id as project_id',
                'c.name as project_name',
            ]);

        // 4. Transformácia výslednej kolekcie na JSON formát pre frontend (ostáva rovnaká)
        $transformedCollection = collect($paginator->items())->map(function ($row) {
            $summary = trim(strip_tags((string) $row->agenda));
            $summary = mb_strimwidth($summary, 0, 180, '…');

            return [
                'id' => (int) $row->id,
                'projectId' => (int) $row->project_id,
                'projectName' => $row->project_name ?? ('Projekt #'.$row->project_id),
                'title' => 'Konzultácia',
                'type' => $row->type,
                'date' => Carbon::parse($row->scheduled_at)->format('d.m.Y'),
                'duration' => (int) ($row->duration ?? 60),
                'summary' => $summary,
                'actionItems' => [],
            ];
        });

        return $paginator->setCollection($transformedCollection);
    }

    private function consultationTypeFromNotes(string $notes): string
    {
        if (preg_match('/^Typ:\s*(.+)$/mi', $notes, $matches) !== 1) {
            return 'personal';
        }

        $value = mb_strtolower(trim($matches[1]));

        if (in_array($value, ['online', 'online (videohovor)'], true)) {
            return 'online';
        }

        if (in_array($value, ['personal', 'osobne', 'osobné'], true)) {
            return 'personal';
        }

        if (in_array($value, ['written', 'písomná / e-mail', 'pisomna / e-mail'], true)) {
            return 'written';
        }

        return 'personal';
    }

    private function formatMilestone(Milestone $milestone): array
    {
        return [
            'id' => $milestone->id,
            'title' => $milestone->name,
            'dueDate' => $milestone->deadline?->format('Y-m-d'),
            'status' => $this->normalizeMilestoneStatus($milestone->status),
            'description' => $milestone->comments,
            'comments' => $this->milestoneComments($milestone->id),
        ];
    }

    private function normalizeMilestoneStatus(?string $status): string
    {
        $value = mb_strtolower(trim((string) $status));

        if (
            in_array($value, ['completed', 'approved', 'schvalene', 'schválené'], true)
            || str_contains($value, 'dokončen')
            || str_contains($value, 'dokon')
            || preg_match('/\bschválen/u', $value) === 1
            || preg_match('/\bschvalen/u', $value) === 1
        ) {
            return 'completed';
        }

        if (str_contains($value, 'reject') || str_contains($value, 'zamiet')) {
            return 'rejected';
        }

        if (
            in_array($value, ['pending_approval', 'pending_review'], true)
            || str_contains($value, 'pending_approval')
            || str_contains($value, 'pending_review')
            || str_contains($value, 'čaká na schválenie')
        ) {
            return 'pending_approval';
        }

        if (
            in_array($value, ['in_progress', 'active'], true)
            || str_contains($value, 'progress')
            || str_contains($value, 'prebie')
            || str_contains($value, 'aktív')
        ) {
            return 'in_progress';
        }

        if (str_contains($value, 'pending')) {
            return 'pending';
        }

        return 'pending';
    }

    private function previousMilestonesCompleted(Application $application, Milestone $milestone): bool
    {
        $ordered = $application->milestones()
            ->orderBy('deadline')
            ->orderBy('id')
            ->get();

        foreach ($ordered as $item) {
            if ($item->id === $milestone->id) {
                return true;
            }

            if (! $this->isMilestoneCompleted($item->status)) {
                return false;
            }
        }

        return true;
    }

    private function storeMilestoneComment(Milestone $milestone, int $userId, string $text): void
    {
        $callId = Application::query()->whereKey($milestone->project_id)->value('call_id');

        if ($callId === null) {
            return;
        }

        $legacyMilestoneId = DB::table('milestone')
            ->where('call_id', $callId)
            ->where('name', $milestone->name)
            ->value('id');

        if ($legacyMilestoneId === null) {
            return;
        }

        DB::table('milestone_comments')->insert([
            'milestone_id' => $legacyMilestoneId,
            'user_id' => $userId,
            'parent_comment_id' => null,
            'comment_text' => $text,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function milestoneComments(int $milestoneId)
    {
        return DB::table('milestone_comments as mc')
            ->join('users as u', 'u.id', '=', 'mc.user_id')
            ->where('mc.milestone_id', $milestoneId)
            ->orderBy('mc.created_at')
            ->get([
                'mc.id',
                'mc.comment_text',
                'mc.created_at',
                'u.name',
                'u.surname',
                'u.email',
            ])
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'author' => trim(($row->name ?? '').' '.($row->surname ?? '')) ?: ($row->email ?? 'Používateľ'),
                    'date' => Carbon::parse($row->created_at)->format('d.m.Y'),
                    'text' => (string) $row->comment_text,
                ];
            })
            ->values();
    }

    private function isMilestoneCompleted(?string $status): bool
    {
        return $this->normalizeMilestoneStatus($status) === 'completed';
    }

    private function resolveLanguageId(Request $request): int
    {
        $locale = mb_strtolower((string) $request->header('X-Locale', 'sk'));

        return $locale === 'en'
            ? LanguageType::ENGLISH->value
            : LanguageType::SLOVAK->value;
    }

    private function projectStatus(Application $application): string
    {
        $status = Application::with(['status'])->where('id', $application->id)
            ->first();
        return $status->status->name ?? '';
    }
}
