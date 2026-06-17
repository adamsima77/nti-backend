<?php

namespace Modules\Teams\Http\Controllers;

use App\Services\Exports\QueuedExportService;
use App\Http\Controllers\Controller;
use App\Services\Pdf\PdfService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Emails\TeamInviteMail;
use Modules\Students\Models\Student;
use Modules\Teams\Models\Team;
use Modules\Teams\Models\TeamInvitation;
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
        $team->loadMissing(['members', 'applications']);

        $members = $team->members->map(function (User $user) use ($roleMap) {
            $roleName = $roleMap->get((int) $user->pivot->team_role_id, 'Člen tímu');
            return [
                'id'    => $user->id,
                'name'  => trim($user->name.' '.($user->surname ?? '')),
                'email' => $user->email,
                'role'  => $roleName,
            ];
        })->values()->all();


        $applications = $team->applications->map(function ($app) {
            return [
                'id'           => $app->id,
                'submitted_at' => $app->submitted_at?->format('Y-m-d H:i:s') ?? null,


                'status' => $app->status ? [
                    'id'   => $app->status->id,
                    'name' => $app->status->name,
                ] : null,
            ];
        })->values()->all();

        $me = $team->members->firstWhere('id', $userId);
        $myRole = $me ? $roleMap->get((int) $me->pivot->team_role_id, 'Člen tímu') : null;

        return [
            'id'           => $team->id,
            'name'         => $team->name,
            'description'  => null,
            'myRole'       => $myRole ?? '',
            'createdAt'    => $team->created_at?->format('Y-m-d') ?? '',
            'members'      => $members,
            'applications' => $applications,
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Team::class);

        $roleMap = TeamRole::query()->pluck('name', 'id');
        $userId = (int) $request->user()->id;


        if ($request->user()->isAdmin() || $request->user()->isSuperAdmin()) {
            $teams = Team::query()->with(['members', 'applications'])->get();
        } else {
            $teams = Team::query()
                ->with(['members', 'applications'])
                ->whereHas('members', fn ($q) => $q->where('user_id', $userId))
                ->get();
        }

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

        // Determine the user's preferred language for the upcoming emails
        $lang = (string) ($request->header('X-Locale') ?? $request->query('lang', 'sk'));
        if (! in_array($lang, ['sk', 'en'], true)) {
            $lang = 'sk';
        }

        $team = DB::transaction(function () use ($validated, $request, $invitedUsers, $lang) {
            // 1. Create the team
            $team = Team::create([
                'name' => $validated['name'],
            ]);

            // 2. Set the creator as the Team Leader
            $teamleader = TeamRole::where('name', 'Vedúci tímu')->firstOrFail();
            $creator = $request->user();

            $creator->teams()->attach($team->id, [
                'team_role_id' => $teamleader->id,
            ]);

            // 3. Process invitations for the validated users
            if ($invitedUsers->isNotEmpty()) {
                $memberRole = TeamRole::query()->where('name', 'Člen tímu')->firstOrFail();
                $inviterName = trim($creator->name . ' ' . ($creator->surname ?? ''));
                $inviterDisplayName = $inviterName !== '' ? $inviterName : (string) $creator->email;

                foreach ($invitedUsers as $invitedUser) {
                    // Skip if the user accidentally invited themselves
                    if ((int) $invitedUser->id === (int) $creator->id) {
                        continue;
                    }

                    // Clean up any stale, unaccepted invitations for this specific team/email
                    TeamInvitation::query()
                        ->where('team_id', $team->id)
                        ->where('email', $invitedUser->email)
                        ->whereNull('accepted_at')
                        ->delete();

                    // Generate secure invitation token
                    $token = \Str::random(64);

                    // Create invitation record
                    TeamInvitation::query()->create([
                        'team_id'      => $team->id,
                        'email'        => $invitedUser->email,
                        'token'        => $token,
                        'team_role_id' => $memberRole->id,
                        'invited_by'   => (int) $creator->id,
                        'expires_at'   => now()->addDays(14),
                    ]);

                    // Dispatch the email invitation immediately
                    \Mail::to($invitedUser->email)->send(new TeamInviteMail(
                        $team,
                        $inviterDisplayName,
                        $invitedUser->email,
                        $token,
                        $lang,
                    ));
                }
            }

            return $team;
        });

        // Load members (this will now only return the team leader initially since members are pending invitation)
        $team->load('members');
        $roleMap = TeamRole::query()->pluck('name', 'id');

        return response()->json([
            'message' => 'Tím bol úspešne vytvorený a pozvánky boli odoslané.',
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

    public function downloadPdf(Team $team, PdfService $pdfService, QueuedExportService $queuedExportService, Request $request)
    {
        $this->authorize('pdf', $team);

        $team->load('members');

        if ($request->boolean('async')) {
            $exportRequest = $queuedExportService->queue(
                (int) $request->user()->id,
                'team_pdf',
                'pdf',
                'pdf',
                'team-report-' . $team->id . '.pdf',
                [
                    'model_class' => Team::class,
                    'model_id' => $team->id,
                    'relations' => ['members'],
                    'view' => 'teams::pdf.team-report',
                    'data_key' => 'team',
                ]
            );

            return response()->json([
                'message' => 'Generovanie exportu bolo zaradené do fronty.',
                'export_request' => [
                    'id' => $exportRequest->id,
                    'export_key' => $exportRequest->export_key,
                    'kind' => $exportRequest->kind,
                    'format' => $exportRequest->format,
                    'status' => $exportRequest->status,
                    'file_name' => $exportRequest->file_name,
                    'status_url' => route('api.exports.show', ['exportRequest' => $exportRequest]),
                    'download_url' => route('api.exports.download', ['exportRequest' => $exportRequest]),
                ],
            ], 202);
        }

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

    public function invite(Request $request, Team $team)
    {
        $this->authorize('inviteMember', $team);

        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'team_role_id' => ['nullable', 'integer', 'exists:team_role,id'],
            'role' => ['nullable', 'string', 'max:80'],
        ]);

        $lang = (string) ($request->header('X-Locale') ?? $request->query('lang', 'sk'));
        if (! in_array($lang, ['sk', 'en'], true)) {
            $lang = 'sk';
        }

        $email = mb_strtolower(trim($validated['email']));

        $user = User::query()->where('email', $email)->firstOrFail();

        $isRegisteredStudent = $user->roles()->where('name', 'student')->exists()
            && Student::query()->where('user_id', $user->id)->exists();

        if (! $isRegisteredStudent) {
            return response()->json([
                'message' => 'Pozvánku je možné poslať iba študentovi registrovanému v NTI.',
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

        TeamInvitation::query()
            ->where('team_id', $team->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->delete();

        $token = Str::random(64);

        $invitation = TeamInvitation::query()->create([
            'team_id' => $team->id,
            'email' => $email,
            'token' => $token,
            'team_role_id' => $teamRoleId,
            'invited_by' => (int) $request->user()->id,
            'expires_at' => now()->addDays(14),
        ]);

        $inviterName = trim($request->user()->name.' '.($request->user()->surname ?? ''));

        Mail::to($email)->send(new TeamInviteMail(
            $team,
            $inviterName !== '' ? $inviterName : (string) $request->user()->email,
            $email,
            $token,
            $lang,
        ));

        return response()->json([
            'message' => 'Pozvánka bola odoslaná.',
            'invitation' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'expires_at' => $invitation->expires_at->format('Y-m-d H:i:s'),
            ],
        ], Response::HTTP_CREATED);
    }

    public function acceptInvitation(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:128'],
        ]);

        $invitation = TeamInvitation::query()
            ->where('token', $validated['token'])
            ->with('team')
            ->first();

        if ($invitation === null || $invitation->accepted_at !== null || $invitation->expires_at->isPast()) {
            return response()->json([
                'message' => 'Pozvánka je neplatná alebo expirovaná.',
            ], Response::HTTP_GONE);
        }

        $authUser = $request->user();
        if (mb_strtolower((string) $authUser->email) !== mb_strtolower($invitation->email)) {
            return response()->json([
                'message' => 'Túto pozvánku môžete prijať iba prihlásený ako adresát pozvánky.',
            ], Response::HTTP_FORBIDDEN);
        }

        $isRegisteredStudent = $authUser->roles()->where('name', 'student')->exists()
            && Student::query()->where('user_id', $authUser->id)->exists();

        if (! $isRegisteredStudent) {
            return response()->json([
                'message' => 'Do tímu sa môžu pridať iba študenti s dokončenou registráciou v NTI.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $team = $invitation->team;
        $team->load('members');
        $roleMap = TeamRole::query()->pluck('name', 'id');

        if ($team->members()->where('user_id', $authUser->id)->exists()) {
            $invitation->forceFill(['accepted_at' => now()])->save();

            return response()->json([
                'message' => 'Už ste členom tohto tímu.',
                'team' => $this->formatTeamForStudent($team, $roleMap, (int) $authUser->id),
            ], Response::HTTP_OK);
        }

        DB::transaction(function () use ($team, $authUser, $invitation) {
            $team->members()->attach($authUser->id, [
                'team_role_id' => $invitation->team_role_id,
            ]);
            $invitation->forceFill(['accepted_at' => now()])->save();
        });

        $team->load('members');

        return response()->json([
            'message' => 'Úspešne ste sa pripojili k tímu.',
            'team' => $this->formatTeamForStudent($team, $roleMap, (int) $authUser->id),
        ], Response::HTTP_OK);
    }
}
