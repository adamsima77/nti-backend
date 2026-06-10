<?php

namespace Modules\Students\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Applications\Models\Application;
use Modules\Teams\Models\Team;
use Modules\Teams\Models\TeamRole;

class StudentDashboardController
{
    use AuthorizesRequests;

    // Statuses that count as "approved" (positive outcome, project running or done)
    private const APPROVED_STATUSES = ['Schválené', 'Onboarding', 'Aktívny projekt', 'Ukončené'];

    // Statuses that count as "in process" (still in evaluation pipeline)
    private const IN_PROCESS_STATUSES = ['Podané', 'V hodnotení', 'Vyžiadané doplnenie', 'Pozastavené'];

    // Statuses that count as "rejected"
    private const REJECTED_STATUSES = ['Zamietnuté'];

    // The one status that triggers a required action
    private const SUPPLEMENT_STATUS = 'Vyžiadané doplnenie';

    // Statuses that have milestones shown on the dashboard
    private const ACTIVE_PROJECT_STATUSES = ['Aktívny projekt', 'Onboarding'];

    public function __invoke(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        // --- Teams (loaded first so we can reuse member counts in applications) ---
        $roleMap   = TeamRole::query()->pluck('name', 'id');
        $userTeams = Team::query()
            ->with('members')
            ->whereHas('members', fn ($q) => $q->where('user_id', $userId))
            ->get();

        $teamMemberCounts = $userTeams->mapWithKeys(fn (Team $t) => [$t->id => $t->members->count()]);

        $teams = $userTeams->map(function (Team $team) use ($userId, $roleMap) {
            $me = $team->members->firstWhere('id', $userId);

            return [
                'id'      => $team->id,
                'name'    => $team->name,
                'members' => $team->members->count(),
                'role'    => $me
                    ? ($roleMap->get((int) $me->pivot->team_role_id) ?? 'Člen tímu')
                    : 'Člen tímu',
            ];
        })->values();

        // --- All user applications (lean: no milestone or document relations yet) ---
        $allApplications = Application::query()
            ->with([
                'status:id,name',
                'call:id,name,application_deadline',
                'team:id,name',
            ])
            ->withCount('documents')
            ->whereHas('team.members', fn ($q) => $q->where('user_id', $userId))
            ->latest('id')
            ->get();

        // --- Milestones: second targeted query, only for active project apps ---
        $activeIds = $allApplications
            ->filter(fn ($a) => in_array($a->status?->name, self::ACTIVE_PROJECT_STATUSES))
            ->pluck('id');

        if ($activeIds->isNotEmpty()) {
            Application::query()
                ->with('milestones')
                ->whereIn('id', $activeIds)
                ->get()
                ->each(fn ($a) => $allApplications->find($a->id)?->setRelation('milestones', $a->milestones));
        }

        return response()->json([
            'stats'                        => $this->buildStats($allApplications),
            'applications'                 => $this->buildRecentApplications($allApplications, $teamMemberCounts),
            'actions'                      => $this->buildActions($allApplications),
            'deadlines'                    => $this->buildDeadlines($allApplications),
            'teams'                        => $teams,
            'activeProjectsWithMilestones' => $this->buildActiveProjects($allApplications, $activeIds),
        ]);
    }

    // -------------------------------------------------------------------------

    private function buildStats(Collection $applications): array
    {
        return [
            'total'     => $applications->count(),
            'approved'  => $applications->filter(fn ($a) => in_array($a->status?->name, self::APPROVED_STATUSES))->count(),
            'inProcess' => $applications->filter(fn ($a) => in_array($a->status?->name, self::IN_PROCESS_STATUSES))->count(),
            'rejected'  => $applications->filter(fn ($a) => in_array($a->status?->name, self::REJECTED_STATUSES))->count(),
        ];
    }

    private function buildRecentApplications(Collection $applications, Collection $teamMemberCounts): Collection
    {
        return $applications
            ->take(4)
            ->map(fn ($app) => [
                'id'          => $app->id,
                'title'       => $app->call?->name ?? "Prihláška #{$app->id}",
                'team'        => $app->team?->name,
                'program'     => null, // extend when program translation is needed
                'status'      =>  $this->mapStatusSlug($app->status?->name),
                'submittedAt' => $app->submitted_at?->format('d.m.Y'),
                'members'     => $teamMemberCounts->get($app->team_id, 0),
                'documents'   => $app->documents_count ?? 0,
            ])
            ->values();
    }

    /**
     * Required actions: only applications in "Vyžiadané doplnenie" state.
     */
    private function buildActions(Collection $applications): Collection
    {
        return $applications
            ->filter(fn ($app) => $app->status?->name === self::SUPPLEMENT_STATUS)
            ->map(fn ($app) => [
                'id'      => $app->id,
                'message' => 'Doplňte požadované informácie k prihláške "' . ($app->call?->name ?? "#{$app->id}") . '"',
                'link'    => "/student/prihlasky/{$app->id}",
            ])
            ->values();
    }

    private function mapStatusSlug(?string $statusName): string
    {
        return match($statusName) {
            'Draft'                => 'draft',
            'Podané'               => 'submitted',
            'V hodnotení'          => 'evaluating',
            'Vyžiadané doplnenie'  => 'pending',
            'Schválené'            => 'approved',
            'Zamietnuté'           => 'rejected',
            'Pozastavené'          => 'paused',
            'Onboarding'           => 'onboarding',
            'Aktívny projekt'      => 'active_project',
            'Ukončené'             => 'ended_project',
            default                => 'draft',
        };
    }

    /**
     * Upcoming deadlines from call.application_deadline.
     * Only future deadlines for applications still in the pipeline.
     */
    private function buildDeadlines(Collection $applications): Collection
    {
        $skip = [...self::APPROVED_STATUSES, ...self::REJECTED_STATUSES];

        return $applications
            ->filter(function ($app) use ($skip) {
                $deadline = $app->call?->application_deadline;
                if (! $deadline) {
                    return false;
                }
                if (in_array($app->status?->name, $skip)) {
                    return false;
                }

                return $deadline->isFuture();
            })
            ->sortBy(fn ($app) => $app->call->application_deadline->timestamp)
            ->take(5)
            ->map(fn ($app) => [
                'id'       => $app->id,
                'title'    => $app->call?->name ?? "Prihláška #{$app->id}",
                'deadline' => $app->call->application_deadline->format('d.m.Y'),
                // diffInDays truncates; max(1,...) ensures same-day deadline shows as 1
                'daysLeft' => max(1, (int) now()->diffInDays($app->call->application_deadline, false)),
            ])
            ->values();
    }

    private function buildActiveProjects(Collection $applications, Collection $activeIds): Collection
    {
        if ($activeIds->isEmpty()) {
            return collect();
        }

        return $applications
            ->filter(fn ($a) => $activeIds->contains($a->id))
            ->map(function ($app) {
                $milestones = collect($app->relationLoaded('milestones') ? $app->milestones : [])
                    ->map(fn ($m) => [
                        'id'      => $m->id,
                        'title'   => $m->name,
                        'dueDate' => $m->deadline?->format('d.m.Y'),
                        'status'  => $this->milestoneStatus($m->status),
                    ])
                    ->values();

                return [
                    'id'                  => $app->id,
                    'title'               => $app->call?->name ?? "Projekt #{$app->id}",
                    'team'                => $app->team?->name ?? '',
                    'completedMilestones' => $milestones->where('status', 'completed')->count(),
                    'milestones'          => $milestones,
                ];
            })
            ->values();
    }

    private function milestoneStatus(?string $status): string
    {
        $s = mb_strtolower((string) $status);

        if (str_contains($s, 'complete') || str_contains($s, 'dokon')) {
            return 'completed';
        }
        if (str_contains($s, 'progress') || str_contains($s, 'prebie') || str_contains($s, 'aktu')) {
            return 'in_progress';
        }

        return 'pending';
    }
}
