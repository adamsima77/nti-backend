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
use Modules\Mentorship\Models\Mentorship;
use Modules\Mentorship\Models\MentorshipSession;
use Modules\Mentorship\Models\Milestone;
use Modules\Mentorship\Models\MilestoneComment;
use Modules\Mentorship\Models\MilestoneStatus;
use Modules\Teams\Models\Team;

class MentorshipController extends Controller
{
    use AuthorizesRequests;

    /**
     * Shared eager-load set for every query that returns full project data.
     *
     * NOTE: Application::milestones() must be defined as:
     *   return $this->hasMany(Milestone::class, 'call_id', 'call_id');
     * because project_milestones links to call_id, not application_id.
     *
     * NOTE: Milestone has both a `comments` text column AND a `comments()`
     * HasMany relation. Access the text column via getRawAttribute() — see
     * formatMilestone() below.
     */
    private const PROJECT_WITH = [
        'creator:id,name,surname,email',
        'team.members',
        'call.program.typeOfProgram',
        'call.productOwner:id,name,surname,email',
        'call.organization:id,name',
        'milestones.milestoneStatus',
        'milestones.comments.user',
        'status:id,name',
        'mentorships',
    ];

    // ──────────────────────────────────────────────────────────────
    // Public routes
    // ──────────────────────────────────────────────────────────────

    public function assignMentor(Request $request, Application $application, User $user): JsonResponse
    {
        $this->authorize('assignMentor', Mentorship::class);

        Mentorship::firstOrCreate([
            'mentor_user_id' => $user->id,
            'application_id' => $application->id,
        ]);

        return response()->json(['message' => 'Mentor assigned successfully.'], Response::HTTP_OK);
    }

    public function fetchMentors(Request $request): JsonResponse
    {
        $mentors = User::whereHas('roles', fn ($q) => $q->where('name', 'mentor'))->get();

        return response()->json(['mentors' => $mentors], Response::HTTP_OK);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $mentor   = $this->currentMentor($request);
        $projects = $this->mentorProjects($mentor->id, $request);

        return response()->json([
            'stats' => [
                'totalProjects'          => $projects->count(),
                'activeProjects'         => $projects->where('status', 'active')->count(),
                'pendingMilestones'      => $projects->where('pendingMilestone', true)->count(),
                'consultationsThisMonth' => $projects->sum('consultationsCount'),
            ],

            'projects' => $projects->take(5)->values(),

            'pendingActions' => $projects
                ->filter(fn (array $p) => $p['pendingMilestone'] === true)
                ->take(5)
                ->values()
                ->map(fn (array $p) => [
                    'id'      => 'project-' . $p['id'],
                    'message' => sprintf('Projekt %s čaká na schválenie míľnika.', $p['name']),
                    'link'    => '/mentor/projekty/' . $p['id'],
                ]),

            'recentConsultations' => collect($this->recentConsultations($mentor->id))->take(5)->values(),
        ]);
    }

    public function projects(Request $request): JsonResponse
    {
        $mentor = $this->currentMentor($request);

        return response()->json($this->mentorProjects($mentor->id, $request));
    }

    /**
     * GET /mentor/projects/{id}
     *
     * Single-request detail — milestones (with comments), consultations,
     * team members and product owner all in one payload.
     */
    public function projectDetail(Request $request, int $project): JsonResponse
    {
        $mentor = $this->currentMentor($request);

        $application = Application::query()
            ->with(self::PROJECT_WITH)
            ->whereKey($project)
            ->whereHas('mentorships', fn ($q) => $q->where('mentor_user_id', $mentor->id))
            ->first();

        abort_if($application === null, 404);


        if ($application->milestones->isNotEmpty()) {
            $application->milestones->load(['milestoneStatus', 'comments.user']);
        }

        // Teraz už $application->milestones obsahuje kompletne všetko a formatMilestone() nezlyhá
        $milestones = $application->milestones
            ->sortBy('id')
            ->map(fn (Milestone $m) => $this->formatMilestone($m))
            ->values();

        $completed = $application->milestones
            ->filter(fn (Milestone $m) => $this->isMilestoneCompleted($m))
            ->count();

        $hasPendingApproval = $application->milestones->contains(
            fn (Milestone $m) => $this->milestoneStatusSlug($m) === 'pending_approval',
        );

        $mentorship = $application->mentorships->firstWhere('mentor_user_id', $mentor->id);

        return response()->json([
            'id'         => $application->id,
            'name'       => $application->call?->name ?? ('Projekt #' . $application->id),
            'teamName'   => $application->team?->name,
            'program'    => $application->call?->program?->typeOfProgram?->name
                ?? $application->call?->program?->name,
            'status'     => $this->projectStatus($application),
            'assignedAt' => $mentorship?->created_at?->format('d.m.Y'),

            'productOwner' => [
                'name'         => trim(
                    ($application->call?->productOwner?->name ?? '') . ' ' .
                    ($application->call?->productOwner?->surname ?? '')
                ),
                'email'        => $application->call?->productOwner?->email ?? '',
                'organization' => $application->call?->organization?->name ?? '',
            ],

            'teamMembers' => $application->team
                ? $application->team->members->map(fn (User $m) => [
                    'id'   => $m->id,
                    'name' => trim(($m->name ?? '') . ' ' . ($m->surname ?? ''))
                        ?: ($m->email ?? ('Používateľ #' . $m->id)),
                    'role' => 'Člen tímu',
                ])->values()
                : [],

            'milestones'          => $milestones,
            'consultations'       => $this->consultationsForProjectDetailed($mentor->id, $project),
            'milestonesCompleted' => $completed,
            'milestonesTotal'     => $application->milestones->count(),
            'pendingMilestone'    => $hasPendingApproval,
        ]);
    }
    public function consultations(Request $request): JsonResponse
    {
        $mentor = $this->currentMentor($request);

        return response()->json($this->allConsultations($mentor->id, $request));
    }

    public function projectMilestones(Request $request, int $project): JsonResponse
    {
        $mentor      = $this->currentMentor($request);
        $application = $this->mentorApplicationOrFail($mentor->id, $project);

        // Safeguard against null collection mappings
        $milestones = $application->milestones ?? collect();

        return response()->json(
            $milestones
                ->map(fn (Milestone $m) => $this->formatMilestone($m))
                ->values(),
        );
    }

    public function updateMilestone(Request $request, int $project, int $milestone): JsonResponse
    {
        $mentor      = $this->currentMentor($request);
        $application = $this->mentorApplicationOrFail($mentor->id, $project);

        if($application->active_status == 7){ //Paused application
            abort(403, "You cant update application that has been paused !");
        }

        $user = $request->user(); // Načítanie prihláseného usera (mentora)

        $hasEditAny  = $user->hasPermission('mentorship.edit_any');
        $hasEditOwn  = $user->hasPermission('mentorship.edit_own');

        // Ak nemá globálny prístup, ani nevlastní tento projekt, vyhodíme 403 Forbidden
        if (! $hasEditAny && ! ($hasEditOwn)) {
            abort(403, 'Nemáte oprávnenie na úpravu tohto míľnika.');
        }
        /** @var Milestone $milestoneModel */
        $milestoneModel = $application->milestones->firstWhere('id', $milestone);

        abort_if($milestoneModel === null, 404);

        $currentSlug = $this->milestoneStatusSlug($milestoneModel);

        $validated = $request->validate([
            'status'     => ['required', 'string', 'in:in_progress,completed,approved,returned,rejected'],
            'comment'    => ['nullable', 'string', 'max:5000'],
            'start_date' => ['nullable', 'date'],
            'deadline'   => ['nullable', 'date'],
        ]);

        $requestedStatus = $validated['status'];

        $targetSlug = match ($requestedStatus) {
            'in_progress'           => 'in_progress',
            'completed', 'approved' => 'completed',
            'returned'              => 'returned',
            'rejected'              => 'rejected',
            default                 => null,
        };

        if ($targetSlug === null) {
            return response()->json(['message' => 'Neplatný požadovaný stav.'], 422);
        }

        // ── VALIDÁCIA KOMENTÁRA (vrátenie / zamietnutie) ──────────────────────
        if (in_array($targetSlug, ['returned', 'rejected'], true)) {
            $request->validate([
                'comment' => ['required', 'string', 'min:20'],
            ], [
                'comment.min' => 'Pre vrátenie alebo zamietnutie míľnika musíte zadať odôvodnenie (min. 20 znakov).',
            ]);
        }

        // ── AGILNÝ RE-DATING ──────────────────────────────────────────────────
        if (! in_array($currentSlug, ['completed', 'rejected'], true)) {
            $dateUpdates = [];

            if ($currentSlug === 'pending' && ! empty($validated['start_date'])) {
                $dateUpdates['start_date'] = $validated['start_date'];
            }

            if (! empty($validated['deadline'])) {
                $dateUpdates['deadline'] = $validated['deadline'];
            }

            if (! empty($dateUpdates)) {
                $milestoneModel->update($dateUpdates);
                $milestoneModel->refresh();
            }
        }

        // ── UNLOCK: pending → in_progress ────────────────────────────────────
        if ($requestedStatus === 'in_progress') {
            if ($currentSlug !== 'pending') {
                return response()->json([
                    'message' => 'Míľnik nie je v stave „Plánované" a nemôže byť odomknutý.',
                ], 422);
            }

            if (! $this->previousMilestonesCompleted($application, $milestoneModel)) {
                return response()->json([
                    'message' => 'Najprv musíte schváliť predchádzajúce míľniky.',
                ], 422);
            }

            $inProgressStatus = MilestoneStatus::where('name', 'V riešení')->firstOrFail();
            $oldStatusName = $milestoneModel->milestoneStatus?->name;

            $milestoneModel->update(['milestone_status_id' => $inProgressStatus->id]);

            $fresh = $milestoneModel->fresh();
            abort_if($fresh === null, 500);
            $fresh->load(['milestoneStatus', 'comments.user']);
            $fresh->setRelation('application', $application);

            // ZMENA TU: Posiela sa originálny názov "V riešení" namiesto 'in_progress'
            event(new MilestoneStatusChanged(
                $fresh,
                $oldStatusName,
                $inProgressStatus->name,
                $mentor,
                $this->resolveLanguageId($request),
            ));

            return response()->json($this->formatMilestone($fresh));
        }

        // ── MENTOR REVIEW GUARDS ──────────────────────────────────────────────
        if (in_array($targetSlug, ['completed', 'approved'], true) && $currentSlug !== 'pending_approval') {
            return response()->json([
                'message' => 'Tento míľnik momentálne nie je pripravený na schválenie mentora (študent ho ešte neodovzdal).',
            ], 422);
        }

        if (
            in_array($targetSlug, ['returned', 'rejected'], true)
            && ! in_array($currentSlug, ['pending_approval', 'in_progress', 'returned'], true)
        ) {
            return response()->json([
                'message' => 'Tento míľnik momentálne nemôžete vrátiť ani zamietnuť.',
            ], 422);
        }

        if (! $this->previousMilestonesCompleted($application, $milestoneModel)) {
            return response()->json([
                'message' => 'Najprv musíte schváliť predchádzajúce míľniky.',
            ], 422);
        }

        $targetDbName = match ($targetSlug) {
            'completed' => 'Schválené',
            'returned'  => 'Vrátené na doplnenie',
            'rejected'  => 'Zamietnuté',
        };

        $targetStatus  = MilestoneStatus::where('name', $targetDbName)->firstOrFail();
        $oldStatusName = $milestoneModel->milestoneStatus?->name;

        $milestoneModel->update(['milestone_status_id' => $targetStatus->id]);

        if (in_array($targetSlug, ['returned', 'rejected'], true) && filled($request->input('comment'))) {
            MilestoneComment::create([
                'milestone_id' => $milestoneModel->id,
                'user_id'      => $mentor->id,
                'comment_text' => (string) $request->input('comment'),
            ]);
        }

        $freshMilestone = $milestoneModel->fresh();
        abort_if($freshMilestone === null, 500);
        $freshMilestone->load(['milestoneStatus', 'comments.user']);
        $freshMilestone->setRelation('application', $application);

        // ZMENA TU: Posiela sa reálny slovenský názov z DB ($targetStatus->name) namiesto $targetSlug
        event(new MilestoneStatusChanged(
            $freshMilestone,
            $oldStatusName,
            $targetStatus->name,
            $mentor,
            $this->resolveLanguageId($request),
        ));

        return response()->json($this->formatMilestone($freshMilestone));
    }

    public function updateMilestoneDates(Request $request, int $project, int $milestone): JsonResponse
    {
        $mentor      = $this->currentMentor($request);
        $application = $this->mentorApplicationOrFail($mentor->id, $project);

        if($application->active_status == 7){ //Paused application
           abort(403, "You cant update application that has been paused !");
        }

        /** @var Milestone $milestoneModel */
        $milestoneModel = $application->milestones->firstWhere('id', $milestone);

        abort_if($milestoneModel === null, 404);

        $currentSlug = $this->milestoneStatusSlug($milestoneModel);

        // Terminal states: nothing is editable
        if (in_array($currentSlug, ['completed'], true)) {
            return response()->json([
                'message' => 'Dátumy uzavretého míľnika nie je možné upraviť.',
            ], 422);
        }

        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'deadline'   => ['nullable', 'date'],
        ]);

        // Reject completely empty requests
        if (empty($validated['start_date']) && empty($validated['deadline'])) {
            return response()->json(['message' => 'Žiadne dátumy neboli zadané.'], 422);
        }

        $dateUpdates = [];

        // start_date: Plánované only
        if (! empty($validated['start_date'])) {
            if ($currentSlug !== 'pending') {
                return response()->json([
                    'message' => 'Dátum začatia je možné zmeniť len v stave „Plánované".',
                ], 422);
            }
            $dateUpdates['start_date'] = $validated['start_date'];
        }

        // deadline: every non-terminal state
        if (! empty($validated['deadline'])) {
            $dateUpdates['deadline'] = $validated['deadline'];
        }

        if (empty($dateUpdates)) {
            return response()->json(['message' => 'Žiadne platné dátumy neboli zadané.'], 422);
        }

        $milestoneModel->update($dateUpdates);

        $fresh = $milestoneModel->fresh();
        abort_if($fresh === null, 500);
        $fresh->load(['milestoneStatus', 'comments.user']);

        return response()->json($this->formatMilestone($fresh));
    }
    /**
     * GET  /mentor/projects/{project}/consultations  — list
     * POST /mentor/projects/{project}/consultations  — create
     */
    public function projectConsultations(Request $request, int $project): JsonResponse
    {
        $mentor = $this->currentMentor($request);
        $this->mentorApplicationOrFail($mentor->id, $project);

        if ($request->isMethod('post')) {
            return $this->handleStoreConsultation($request, $mentor, $project);
        }

        return response()->json(
            $this->consultationsForProjectDetailed($mentor->id, $project),
        );
    }

    public function storeConsultation(Request $request, int $project): JsonResponse
    {
        return $this->projectConsultations($request, $project);
    }

    /**
     * PUT /mentor/projects/{project}/consultations/{session}
     */
    public function updateConsultation(Request $request, int $project, int $session): JsonResponse
    {
        $mentor = $this->currentMentor($request);

        $exists = DB::table('mentorship_session as ms')
            ->join('mentorship as m', 'm.id', '=', 'ms.mentorship_id')
            ->where('ms.id', $session)
            ->where('m.mentor_user_id', $mentor->id)
            ->where('m.application_id', $project)
            ->exists();

        abort_if(! $exists, 404);

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'type'         => ['required', 'in:online,offline'],
            'scheduled_at' => ['required', 'date'],
            'meeting_url'  => ['nullable', 'url'],
            'agenda'       => ['nullable', 'string', 'max:5000'],
            'duration'     => ['required', 'integer', 'min:1'],
        ]);

        if ($validated['type'] === 'online' && empty($validated['meeting_url'])) {
            return response()->json([
                'message' => 'Pre online stretnutie je potrebné zadať odkaz.',
            ], 422);
        }

        DB::table('mentorship_session')
            ->where('id', $session)
            ->update([
                'title'        => $validated['title'],
                'type'         => $validated['type'],
                'scheduled_at' => $validated['scheduled_at'],
                'meeting_url'  => $validated['meeting_url'] ?? null,
                'agenda'       => $validated['agenda'] ?? null,
                'duration'     => $validated['duration'],
                'updated_at'   => now(),
            ]);

        $row = DB::table('mentorship_session')->where('id', $session)->first();

        return response()->json([
            'id'          => (int) $row->id,
            'title'       => $row->title ?? 'Konzultácia',
            'date'        => Carbon::parse($row->scheduled_at)->format('d.m.Y'),
            'duration'    => (int) ($row->duration ?? 60),
            'type'        => $row->type,
            'summary'     => mb_strimwidth(trim(strip_tags((string) $row->agenda)), 0, 200, '…'),
            'actionItems' => [],
        ]);
    }

    /**
     * DELETE /mentor/projects/{project}/consultations/{session}
     */
    public function deleteConsultation(Request $request, int $project, int $session): JsonResponse
    {
        $mentor = $this->currentMentor($request);

        $mentorshipId = DB::table('mentorship_session as ms')
            ->join('mentorship as m', 'm.id', '=', 'ms.mentorship_id')
            ->where('ms.id', $session)
            ->where('m.mentor_user_id', $mentor->id)
            ->where('m.application_id', $project)
            ->value('ms.mentorship_id');

        abort_if($mentorshipId === null, 404);

        DB::table('mentorship_session')->where('id', $session)->delete();

        return response()->json(['message' => 'Konzultácia bola odstránená.']);
    }

    public function storeFeedback(Request $request, int $project): JsonResponse
    {
        $mentor = $this->currentMentor($request);
        $this->mentorApplicationOrFail($mentor->id, $project);

        $request->validate([
            'message'        => ['required', 'string', 'max:5000'],
            'rating'         => ['nullable', 'integer', 'min:1', 'max:5'],
            'recommendation' => ['nullable', 'string', 'max:5000'],
        ]);

        return response()->json(['message' => 'Feedback bol prijatý.']);
    }

    // ──────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────

    private function handleStoreConsultation(Request $request, User $mentor, int $project): JsonResponse
    {
        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'type'         => ['required', 'in:online,offline'],
            'scheduled_at' => ['required', 'date'],
            'meeting_url'  => ['nullable', 'url'],
            'agenda'       => ['nullable', 'string', 'max:5000'],
            'duration'     => ['required', 'integer', 'min:1'],
        ]);

        if ($validated['type'] === 'online' && empty($validated['meeting_url'])) {
            return response()->json([
                'message' => 'Pre online stretnutie je potrebné zadať odkaz.',
            ], 422);
        }

        $mentorshipId = $this->mentorshipId($mentor->id, $project);

        $mentorSession = MentorshipSession::create([
            'mentorship_id' => $mentorshipId,
            'created_by'    => $mentor->id,
            'title'         => $validated['title'],
            'type'          => $validated['type'],
            'meeting_url'   => $validated['meeting_url'] ?? null,
            'scheduled_at'  => $validated['scheduled_at'],
            'agenda'        => $validated['agenda'] ?? null,
            'duration'      => $validated['duration'],
            'status'        => 'scheduled',
        ]);

        event(new MentorSessionEvent($mentorSession));

        return response()->json(['message' => 'Mentoringové sedenie bolo naplánované.'], 201);
    }

    private function currentMentor(Request $request): User
    {
        $user = $request->user();

        abort_if($user === null, 401);
        abort_if(! $user->isMentor() && ! $user->isAdmin() && ! $user->isSuperAdmin(), 403);

        return $user;
    }

    private function mentorProjects(int $mentorId, Request $request)
    {
        $query = Application::query()
            ->with(self::PROJECT_WITH)
            ->whereHas('mentorships', fn ($q) => $q->where('mentor_user_id', $mentorId));

        if ($request->filled('status')) {
            $query->where('active_status', $request->status);
        }

        if ($request->filled('program')) {
            $query->whereHas('call', fn ($q) => $q->where('program_id', $request->program));
        }

        $paginator   = $query->orderByDesc('created_by')->paginate(30);
        $transformed = collect($paginator->items())->map(function (Application $application) use ($mentorId) {
            $milestones = $application->milestones
                ->map(fn (Milestone $m) => $this->formatMilestone($m))
                ->values();

            $completed = $application->milestones
                ->filter(fn (Milestone $m) => $this->isMilestoneCompleted($m))
                ->count();

            $nextMilestone = $application->milestones
                ->first(fn (Milestone $m) => ! $this->isMilestoneCompleted($m));

            $hasPendingApproval = $application->milestones->contains(
                fn (Milestone $m) => $this->milestoneStatusSlug($m) === 'pending_approval',
            );

            return [
                'id'       => $application->id,
                'name'     => $application->call?->name ?? ('Projekt #' . $application->id),
                'teamName' => $application->team?->name,
                'program'  => $application->call?->program?->typeOfProgram?->name
                    ?? $application->call?->program?->name,
                'status'     => $this->projectStatus($application),
                'assignedAt' => $application->mentorships
                    ->firstWhere('mentor_user_id', $mentorId)
                    ?->created_at
                    ?->format('d.m.Y'),

                'productOwner' => $application->creator ? [
                    'name'  => trim(($application->creator->name ?? '') . ' ' . ($application->creator->surname ?? ''))
                        ?: ($application->creator->email ?? 'Mentor'),
                    'email' => $application->creator->email,
                ] : null,

                'teamMembers' => $application->team
                    ? $application->team->members->map(fn (User $m) => [
                        'id'   => $m->id,
                        'name' => trim(($m->name ?? '') . ' ' . ($m->surname ?? ''))
                            ?: ($m->email ?? ('Používateľ #' . $m->id)),
                        'role' => 'Člen tímu',
                    ])->values()
                    : [],

                'teamSize'            => $application->team ? $application->team->members->count() : 0,
                'consultationsCount'  => $this->consultationsCount($mentorId, (int) $application->id),
                'milestonesCompleted' => $completed,
                'milestonesTotal'     => $application->milestones->count(),
                'nextMilestone'       => $nextMilestone?->name,
                'pendingMilestone'    => $hasPendingApproval,
                'milestones'          => $milestones,
            ];
        })->values();

        $paginator->setCollection($transformed);

        return $paginator;
    }

    /**
     * Load a single application, scoped to the mentor, with milestones and
     * their relations eagerly loaded. Aborts 404 if not found or not owned.
     */
    private function mentorApplicationOrFail(int $mentorId, int $projectId): Application
    {
        $application = Application::query()
            ->with(self::PROJECT_WITH)
            ->whereKey($projectId)
            ->whereHas('mentorships', fn ($q) => $q->where('mentor_user_id', $mentorId))
            ->first();

        abort_if($application === null, 404);

        return $application;
    }

    private function mentorshipId(int $mentorId, int $projectId): int
    {
        $id = Mentorship::where('mentor_user_id', $mentorId)
            ->where('application_id', $projectId)
            ->value('id');

        abort_if($id === null, 404);

        return (int) $id;
    }

    private function consultationsCount(int $mentorId, int $projectId): int
    {
        return DB::table('mentorship_session as ms')
            ->join('mentorship as m', 'm.id', '=', 'ms.mentorship_id')
            ->where('m.mentor_user_id', $mentorId)
            ->where('m.application_id', $projectId)
            ->count();
    }

    private function consultationsForProjectDetailed(int $mentorId, int $projectId)
    {
        return DB::table('mentorship_session as ms')
            ->join('mentorship as m', 'm.id', '=', 'ms.mentorship_id')
            ->where('m.mentor_user_id', $mentorId)
            ->where('m.application_id', $projectId)
            ->orderByDesc('ms.scheduled_at')
            ->get([
                'ms.id', 'ms.title', 'ms.scheduled_at', 'ms.agenda',
                'ms.type', 'ms.duration', 'ms.meeting_url',    // ← meeting_url added
            ])
            ->map(fn ($row) => [
                'id'          => (int) $row->id,
                'title'       => $row->title ?? 'Konzultácia',
                'date'        => Carbon::parse($row->scheduled_at)->format('d.m.Y'),
                'scheduledAt' => $row->scheduled_at,            // ← for edit modal pre-fill
                'duration'    => (int) ($row->duration ?? 60),
                'type'        => $row->type,
                'meetingUrl'  => $row->meeting_url,             // ← for join link
                'summary'     => mb_strimwidth(trim(strip_tags((string) $row->agenda)), 0, 200, '…'),
                'actionItems' => [],
            ])
            ->values();
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
            ->get(['ms.id', 'ms.scheduled_at', 'a.id as project_id', 'c.name as project_name'])
            ->map(fn ($row) => [
                'id'          => (int) $row->id,
                'projectId'   => (int) $row->project_id,
                'projectName' => $row->project_name ?? ('Projekt #' . $row->project_id),
                'date'        => Carbon::parse($row->scheduled_at)->format('d.m.Y'),
            ]);
    }

    private function allConsultations(int $mentorId, Request $request)
    {
        $query = DB::table('mentorship_session as ms')
            ->join('mentorship as m', 'm.id', '=', 'ms.mentorship_id')
            ->join('application as a', 'a.id', '=', 'm.application_id')
            ->leftJoin('call as c', 'c.id', '=', 'a.call_id')
            ->where('m.mentor_user_id', $mentorId);

        if ($request->filled('project_id')) {
            $query->where('a.id', $request->input('project_id'));
        }

        if ($request->filled('type')) {
            $query->where('ms.type', $request->input('type'));
        }

        if ($request->filled('month')) {
            $query->whereRaw("to_char(ms.scheduled_at, 'YYYY-MM') = ?", [$request->input('month')]);
        }

        $paginator = $query->orderByDesc('ms.scheduled_at')
            ->paginate(30, [
                'ms.id', 'ms.title', 'ms.scheduled_at', 'ms.agenda',
                'ms.type', 'ms.duration', 'ms.meeting_url',    // ← added
                'a.id as project_id', 'c.name as project_name',
            ]);

        $transformed = collect($paginator->items())->map(fn ($row) => [
            'id'          => (int) $row->id,
            'projectId'   => (int) $row->project_id,
            'projectName' => $row->project_name ?? ('Projekt #' . $row->project_id),
            'title'       => $row->title ?? 'Konzultácia',
            'type'        => $row->type,
            'meetingUrl'  => $row->meeting_url,                // ← added
            'date'        => Carbon::parse($row->scheduled_at)->format('d.m.Y'),
            'scheduledAt' => $row->scheduled_at,               // ← added
            'duration'    => (int) ($row->duration ?? 60),
            'summary'     => mb_strimwidth(trim(strip_tags((string) $row->agenda)), 0, 180, '…'),
            'actionItems' => [],
        ]);

        return $paginator->setCollection($transformed);
    }

    /**
     * Build the serialisable milestone array for API responses.
     *
     * IMPORTANT: Milestone has both a `comments` text column and a
     * `comments()` HasMany relation. When the relation is loaded, Laravel
     * returns the Collection for `$milestone->comments`. To read the raw text
     * column we use getAttributes() to bypass the relation resolver.
     */
    private function formatMilestone(Milestone $milestone): array
    {

        $descriptionText = $milestone->getAttributes()['comments'] ?? null;


        $commentsCollection = $milestone->comments()->get();
        $docs = $milestone->documents()->get();

        return [
            'id'          => $milestone->id,
            'title'       => $milestone->name,
            'dueDate'     => $milestone->deadline?->format('Y-m-d'),
            'start_date' => $milestone->start_date?->format('Y-m-d'),
            'status'      => $this->milestoneStatusSlug($milestone),
            'description' => $descriptionText,
            'documents' => $docs,
            'comments'    => $commentsCollection->map(fn (MilestoneComment $c) => [
                'id'     => $c->id,
                'author' => trim(($c->user?->name ?? '') . ' ' . ($c->user?->surname ?? ''))
                    ?: ($c->user?->email ?? 'Používateľ'),
                'date'   => $c->created_at?->format('d.m.Y'),
                'text'   => (string) $c->comment_text,
            ])->values(),
        ];
    }

    /**
     * Normalise a MilestoneStatus name to one of our canonical slugs:
     * pending | in_progress | pending_approval | completed | rejected
     */
    private function normalizeMilestoneStatus(?string $statusName): string
    {
        if (! $statusName) {
            return 'pending';
        }

        return match ($statusName) {
            'Plánované'            => 'pending',
            'V riešení'            => 'in_progress',
            'Dokončené'            => 'pending_approval',
            'Schválené'            => 'completed',
            'Zamietnuté'           => 'rejected',
            'Vrátené na doplnenie' => 'returned',   // ← was 'rejected'
            default                => 'pending',
        };
    }

    /**
     * Convenience wrapper — resolves the slug from the already-loaded relation
     * so callers never have to reach into the relation manually.
     */
    private function milestoneStatusSlug(Milestone $milestone): string
    {
        return $this->normalizeMilestoneStatus($milestone->milestoneStatus?->name);
    }

    private function isMilestoneCompleted(Milestone $milestone): bool
    {
        $slug = $this->milestoneStatusSlug($milestone);
        return in_array($slug, ['completed', 'rejected'], true);
    }

    /**
     * Returns true when all milestones with an earlier deadline/id than
     * $milestone are already completed. Uses the in-memory collection to
     * avoid an extra query (milestones must be eager-loaded beforehand).
     */
    private function previousMilestonesCompleted(Application $application, Milestone $milestone): bool
    {
        $milestones = $application->milestones ?? collect();

        if ($milestones->isEmpty()) {
            return true;
        }


        $ordered = $milestones->sortBy('id');

        foreach ($ordered as $item) {
            if ($item->id === $milestone->id) {
                return true;
            }

            if (! $this->isMilestoneCompleted($item)) {
                return false;
            }
        }

        return true;
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
        // status is already eager-loaded via PROJECT_WITH / mentorApplicationOrFail
        return $application->status?->name ?? '';
    }


}
