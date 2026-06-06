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
use Modules\Mentorship\Events\MilestoneStatusChanged;
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
        $projects = $this->mentorProjects($mentor->id);

        return response()->json([
            'stats' => [
                'totalProjects' => $projects->count(),
                'activeProjects' => $projects->where('status', 'active')->count(),
                // Count projects that have at least one milestone awaiting mentor approval.
                'pendingMilestones' => $projects->where('pendingMilestone', true)->count(),
                'consultationsThisMonth' => $projects->sum('consultationsCount'),
            ],
            'projects' => $projects->values(),
            'pendingActions' => $projects
                ->filter(fn (array $project) => $project['pendingMilestone'] === true)
                ->take(5)
                ->values()
                ->map(fn (array $project) => [
                    'id' => 'project-'.$project['id'],
                    'message' => sprintf('Projekt %s čaká na schválenie míľnika.', $project['name']),
                    'link' => '/mentor/projekty/'.$project['id'],
                ]),
            'recentConsultations' => $this->recentConsultations($mentor->id),
        ]);
    }

    public function projects(Request $request): JsonResponse
    {
        $mentor = $this->currentMentor($request);

        return response()->json($this->mentorProjects($mentor->id)->values());
    }

    public function consultations(Request $request): JsonResponse
    {
        $mentor = $this->currentMentor($request);

        return response()->json($this->allConsultations($mentor->id));
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
                'note' => ['required', 'string', 'max:5000'],
            ]);

            $mentorshipId = $this->mentorshipId($mentor->id, $project);

            DB::table('mentorship_session')->insert([
                'mentorship_id' => $mentorshipId,
                'created_by' => $mentor->id,
                'date' => now(),
                'notes' => $validated['note'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'message' => 'Záznam z konzultácie bol uložený.',
            ], 201);
        }

        return response()->json($this->consultationsForProject($mentor->id, $project));
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

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function mentorProjects(int $mentorId)
    {
        $assignments = DB::table('mentorship')
            ->where('mentor_user_id', $mentorId)
            ->orderByDesc('created_at')
            ->get(['id', 'application_id', 'created_at']);

        if ($assignments->isEmpty()) {
            return collect();
        }

        $applications = Application::query()
            ->with([
                'creator:id,name,surname,email',
                'team.members',
                'call.program.typeOfProgram',
                'milestones',
                'status:id,name',
            ])
            ->whereIn('id', $assignments->pluck('application_id'))
            ->get()
            ->keyBy('id');

        return $assignments->map(function ($assignment) use ($applications, $mentorId) {
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
                // Only show "pending approval" if at least one milestone awaits mentor approval.
                'pendingMilestone' => $hasPendingApproval,
                'milestones' => $milestones,
            ];
        })->filter()->values();
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
            ->orderByDesc('ms.date')
            ->where('m.mentor_user_id', $mentorId)
            ->limit(5)
            ->get([
                'ms.id',
                'ms.date',
                'ms.notes',
                'a.id as project_id',
                'c.name as project_name',
            ])
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'projectId' => (int) $row->project_id,
                    'projectName' => $row->project_name ?? ('Projekt #'.$row->project_id),
                    'summary' => mb_strimwidth(trim(strip_tags((string) $row->notes)), 0, 160, '…'),
                    'date' => Carbon::parse($row->date)->format('d.m.Y'),
                ];
            });
    }

    private function consultationsForProject(int $mentorId, int $projectId)
    {
        return DB::table('mentorship_session as ms')
            ->join('mentorship as m', 'm.id', '=', 'ms.mentorship_id')
            ->where('m.mentor_user_id', $mentorId)
            ->where('m.application_id', $projectId)
            ->orderByDesc('ms.date')
            ->get([
                'ms.id',
                'ms.date',
                'ms.notes',
            ])
            ->map(function ($row) {
                $type = $this->consultationTypeFromNotes((string) $row->notes);

                return [
                    'id' => (int) $row->id,
                    'title' => 'Konzultácia',
                    'date' => Carbon::parse($row->date)->format('d.m.Y'),
                    'duration' => 60,
                    'type' => $type,
                    'summary' => mb_strimwidth(trim(strip_tags((string) $row->notes)), 0, 200, '…'),
                    'actionItems' => [],
                ];
            });
    }

    private function allConsultations(int $mentorId)
    {
        return DB::table('mentorship_session as ms')
            ->join('mentorship as m', 'm.id', '=', 'ms.mentorship_id')
            ->join('application as a', 'a.id', '=', 'm.application_id')
            ->leftJoin('call as c', 'c.id', '=', 'a.call_id')
            ->orderByDesc('ms.date')
            ->where('m.mentor_user_id', $mentorId)
            ->get([
                'ms.id',
                'ms.date',
                'ms.notes',
                'a.id as project_id',
                'c.name as project_name',
            ])
            ->map(function ($row) {
                $type = $this->consultationTypeFromNotes((string) $row->notes);
                $summary = trim(strip_tags((string) $row->notes));
                $summary = mb_strimwidth($summary, 0, 180, '…');

                return [
                    'id' => (int) $row->id,
                    'projectId' => (int) $row->project_id,
                    'projectName' => $row->project_name ?? ('Projekt #'.$row->project_id),
                    'title' => 'Konzultácia',
                    'type' => $type,
                    'date' => Carbon::parse($row->date)->format('d.m.Y'),
                    'duration' => 60,
                    'summary' => $summary,
                    'actionItems' => [],
                ];
            })
            ->values();
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
        $statusName = mb_strtolower((string) $application->status?->name);

        if ($statusName === '') {
            return 'active';
        }

        if (str_contains($statusName, 'ukon') || str_contains($statusName, 'completed')) {
            return 'completed';
        }

        if (str_contains($statusName, 'návrh') || str_contains($statusName, 'draft')) {
            return 'draft';
        }

        return 'active';
    }
}
