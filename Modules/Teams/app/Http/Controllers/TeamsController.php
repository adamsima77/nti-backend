<?php

namespace Modules\Teams\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Pdf\PdfService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\IdentityAccess\Models\User;
use Modules\Students\Models\Student;
use Modules\Teams\Models\Team;
use Modules\Teams\Models\TeamRole;

class TeamsController extends Controller
{
    use AuthorizesRequests;

    private function resolveMemberRoleId(?string $roleName, ?int $roleId = null): int
    {
        if ($roleId !== null) {
            $role = TeamRole::query()->findOrFail($roleId);
        } else {
            $normalized = strtolower(trim((string) $roleName));
            $isLeaderRole = in_array($normalized, ['team lead', 'team_lead', 'vedúci tímu', 'veduci timu'], true);

            if ($isLeaderRole) {
                throw ValidationException::withMessages([
                    'role' => ['Rolu vedúceho tímu nie je možné priradiť pri pozývaní.'],
                ]);
            }

            $role = TeamRole::query()->where('name', 'Člen tímu')->firstOrFail();
        }

        if ($role->name === 'Vedúci tímu') {
            throw ValidationException::withMessages([
                'team_role_id' => ['Rolu vedúceho tímu nie je možné priradiť pri pozývaní.'],
            ]);
        }

        return (int) $role->id;
    }

    /**
     * Shape returned to the student portal (matches frontend store / TEAMS_FEATURE.md).
     *
     * @return array<string, mixed>
     */
    private function formatTeamForStudent(Team $team, Collection $roleMap, int $userId): array
    {
        $team->loadMissing('members');

        $members = $team->members->map(function (User $user) use ($roleMap) {
            $roleName = $roleMap->get((int) $user->pivot->team_role_id, 'Člen tímu');

            return [
                'id'    => $user->id,
                'name'  => trim($user->name.' '.($user->surname ?? '')),
                'email' => $user->email,
                'role'  => $roleName,
            ];
        })->values()->all();

        $me = $team->members->firstWhere('id', $userId);
        $myRole = $me ? $roleMap->get((int) $me->pivot->team_role_id, 'Člen tímu') : 'Člen tímu';

        return [
            'id'           => $team->id,
            'name'         => $team->name,
            'description'  => null,
            'myRole'       => $myRole,
            'createdAt'    => $team->created_at?->format('Y-m-d') ?? '',
            'members'      => $members,
            'applications' => [],
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Team::class);

        $roleMap = TeamRole::query()->pluck('name', 'id');
        $teams    = Team::with('members')->get();
        $userId   = (int) $request->user()->id;

        return response()->json([
            'teams' => $teams->map(fn (Team $t) => $this->formatTeamForStudent($t, $roleMap, $userId))->values(),
        ], Response::HTTP_OK);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('students::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Team::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'members' => ['sometimes', 'array'],
            'members.*' => ['email', 'distinct'],
        ]);

        $memberEmails = collect($validated['members'] ?? [])
            ->map(fn ($email) => mb_strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values();

        $invitedUsers = collect();

        if ($memberEmails->isNotEmpty()) {
            $invitedUsers = User::query()
                ->whereIn('email', $memberEmails)
                ->whereHas('roles', fn ($q) => $q->where('name', 'student'))
                ->whereIn('id', Student::query()->select('user_id'))
                ->get(['id', 'email']);

            $missingEmails = $memberEmails->diff(
                $invitedUsers->pluck('email')->map(fn ($email) => mb_strtolower((string) $email))
            )->values();

            if ($missingEmails->isNotEmpty()) {
                return response()->json([
                    'message' => 'Do tímu je možné pridať iba študentov registrovaných v NTI.',
                    'invalid_members' => $missingEmails,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $team = DB::transaction(function () use ($validated, $request, $invitedUsers) {
            $team = Team::create([
                'name' => $validated['name'],
            ]);

            $teamleader = TeamRole::where('name', 'Vedúci tímu')->firstOrFail();

            $request->user()->teams()->attach($team->id, [
                'team_role_id' => $teamleader->id,
            ]);

            if ($invitedUsers->isNotEmpty()) {
                $memberRole = TeamRole::query()->where('name', 'Člen tímu')->firstOrFail();
                $creatorId = (int) $request->user()->id;

                foreach ($invitedUsers as $invitedUser) {
                    if ((int) $invitedUser->id === $creatorId) {
                        continue;
                    }

                    $team->members()->syncWithoutDetaching([
                        (int) $invitedUser->id => ['team_role_id' => $memberRole->id],
                    ]);
                }
            }

            return $team;
        });

        $team->load('members');
        $roleMap = TeamRole::query()->pluck('name', 'id');

        return response()->json([
            'message' => 'Tím bol úspešne vytvorený.',
            'team'    => $this->formatTeamForStudent($team, $roleMap, (int) $request->user()->id),
        ], Response::HTTP_CREATED);
    }

    /**
     * Show the specified resource.
     */
    public function show(Request $request, Team $team)
    {
        $this->authorize('view', $team);

        $team->load('members');
        $roleMap = TeamRole::query()->pluck('name', 'id');

        return response()->json([
            'team' => $this->formatTeamForStudent($team, $roleMap, (int) $request->user()->id),
        ], Response::HTTP_OK);
    }

    public function downloadPdf(Team $team, PdfService $pdfService)
    {
        $this->authorize('pdf', $team);

        $team->load('members');

        return $pdfService->download(
            'teams::pdf.team-report',
            ['team' => $team],
            'team-report-' . $team->id . '.pdf'
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('students::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team)
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
        ]);

        $team->update($validated);

        $updated = $team->fresh();
        $updated->load('members');
        $roleMap = TeamRole::query()->pluck('name', 'id');

        return response()->json([
            'message' => 'Tím bol úspešne aktualizovaný.',
            'team'    => $this->formatTeamForStudent($updated, $roleMap, (int) $request->user()->id),
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        $this->authorize('delete', $team);

        DB::transaction(function () use ($team) {
            $team->members()->detach();
            $team->delete();
        });

        return response()->json([
            'message' => 'Tím bol úspešne odstránený.',
        ], Response::HTTP_OK);
    }

    public function addMember(Request $request, Team $team)
    {
        $this->authorize('addMember', $team);

        $validated = $request->validate([
            'email'        => ['required', 'email', 'exists:users,email'],
            'team_role_id' => ['nullable', 'integer', 'exists:team_role,id'],
            'role'         => ['nullable', 'string', 'max:80'],
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        $isRegisteredStudent = $user->roles()->where('name', 'student')->exists()
            && Student::query()->where('user_id', $user->id)->exists();

        if (!$isRegisteredStudent) {
            return response()->json([
                'message' => 'Do tímu je možné pridať iba študentov registrovaných v NTI.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($team->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Používateľ je už členom tímu.',
            ], Response::HTTP_CONFLICT);
        }

        $teamRoleId = $this->resolveMemberRoleId(
            $validated['role'] ?? null,
            isset($validated['team_role_id']) ? (int) $validated['team_role_id'] : null
        );

        $team->members()->attach($user->id, [
            'team_role_id' => $teamRoleId,
        ]);

        $team->load('members');
        $roleMap = TeamRole::query()->pluck('name', 'id');

        return response()->json([
            'message' => 'Používateľ bol úspešne pridaný do tímu.',
            'team'    => $this->formatTeamForStudent($team, $roleMap, (int) $request->user()->id),
        ], Response::HTTP_CREATED);
    }

    public function removeMember(Request $request, Team $team, User $user)
    {
        $this->authorize('removeMember', $team);

        if (!$team->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Používateľ nie je členom tímu.',
            ], Response::HTTP_NOT_FOUND);
        }

        $team->members()->detach($user->id);

        return response()->json([
            'message' => 'Používateľ bol úspešne odstránený z tímu.',
        ], Response::HTTP_OK);
    }
}
