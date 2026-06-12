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

        // --- 1. TEAMS (Strictly limited to 4 records via Eloquent) ---
        $roleMap   = TeamRole::query()->pluck('name', 'id');
        $userTeams = Team::query()
            ->with('members')
            ->whereHas('members', fn ($q) => $q->where('user_id', $userId))
            ->take(4)
            ->get();

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

        // --- 2. STATS & RECENT APPLICATIONS (Loaded cleanly via Eloquent scopes) ---
        // Fetch snapshot profiles of applications belonging to the user's teams
        $allApplications = Application::query()
            ->with([
                'status:id,name',
                'call:id,name,application_deadline',
                'team:id,name',
                'team.members', // Eager loaded to dynamically compute counts safely without raw aggregates
            ])
            ->withCount('documents')
            ->whereHas('team.members', fn ($q) => $q->where('user_id', $userId))
            ->latest('id')
            ->get();

        // --- 3. TARGETED MILESTONES EAGER-LOADING ---
        $activeIds = $allApplications
            ->filter(fn ($a) => in_array($a->status?->name, self::ACTIVE_PROJECT_STATUSES))
            ->pluck('id');

        if ($activeIds->isNotEmpty()) {
            Application::query()
                ->with('milestones.milestoneStatus')
                ->whereIn('id', $activeIds)
                ->get()
                ->each(fn ($a) => $allApplications->find($a->id)?->setRelation('milestones', $a->milestones));
        }

        // Slice the applications collection downward right before compiling data fields
        $recentApplications = $allApplications->take(4);

        return response()->json([
            'stats'                        => $this->buildStats($allApplications),
            'applications'                 => $this->buildRecentApplications($recentApplications),
            'actions'                      => $this->buildActions($recentApplications),
            'deadlines'                    => $this->buildDeadlines($recentApplications),
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

    private function buildRecentApplications(Collection $applications): Collection
    {
        return $applications
            ->map(fn ($app) => [
                'id'          => $app->id,
                'title'       => $app->call?->name ?? "Prihláška #{$app->id}",
                'team'        => $app->team?->name,
                'program'     => null,
                'status'      => $this->mapStatusSlug($app->status?->name),
                'submittedAt' => $app->submitted_at?->format('d.m.Y'),
                'members'     => $app->team && $app->team->relationLoaded('members') ? $app->team->members->count() : 0,
                'documents'   => $app->documents_count ?? 0,
            ])
            ->values();
    }

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

    private function mapStatusSlug(?string $statusName=""): string
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

    private function buildDeadlines(Collection $applications): Collection
    {
        $skip = [...self::APPROVED_STATUSES, ...self::REJECTED_STATUSES];

        return $applications
            ->filter(function ($app) use ($skip) {
                $deadline = $app->call?->application_deadline;
                if (! $deadline || in_array($app->status?->name, $skip)) {
                    return false;
                }
                return $deadline->isFuture();
            })
            ->sortBy(fn ($app) => $app->call->application_deadline->timestamp)
            ->map(fn ($app) => [
                'id'       => $app->id,
                'title'    => $app->call?->name ?? "Prihláška #{$app->id}",
                'deadline' => $app->call->application_deadline->format('d.m.Y'),
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
                $rawMilestones = $app->milestones ?? collect();

                $milestones = collect($rawMilestones)
                    ->map(function ($m) {
                        $statusName = $m->milestoneStatus?->name ?? '';

                        $normalizedStatus = match ($statusName) {
                            'Schválené', 'Dokončené' => 'completed',
                            'V riešení'             => 'in_progress',
                            'Vrátené na doplnenie'  => 'returned',
                            'Zamietnuté'            => 'rejected',
                            default                 => 'planned',
                        };

                        return [
                            'id'      => $m->id,
                            'title'   => $m->name ?? 'Míľnik',
                            'dueDate' => $m->deadline ? $m->deadline->format('d.m.Y') : '',
                            'status'  => $normalizedStatus,
                        ];
                    })
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
}
