<?php

namespace Modules\Teams\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\IdentityAccess\Models\User;
use Modules\Teams\Models\Team;
use Modules\Teams\Models\TeamRole;

class TeamsController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Team::class);

        $teams = Team::with('members')->get();

        return response()->json([
            'teams' => $teams,
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
        ]);

        $team = DB::transaction(function () use ($validated, $request) {
            $team = Team::create([
                'name' => $validated['name'],
            ]);

            $teamleader = TeamRole::where('name', 'Vedúci tímu')->firstOrFail();

            $request->user()->teams()->attach($team->id, [
                'team_role_id' => $teamleader->id,
            ]);

            return $team;
        });

        return response()->json([
            'message' => 'Tím bol úspešne vytvorený.',
            'team'    => $team->load('members'),
        ], Response::HTTP_CREATED);
    }

    /**
     * Show the specified resource.
     */
    public function show(Team $team)
    {
        $this->authorize('view', $team);

        return response()->json([
            'team' => $team->load('members'),
        ], Response::HTTP_OK);
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

        return response()->json([
            'message' => 'Tím bol úspešne aktualizovaný.',
            'team'    => $team->fresh()->load('members'),
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
            'team_role_id' => ['required', 'integer', 'exists:team_role,id'],
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        if ($team->members()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Používateľ je už členom tímu.',
            ], Response::HTTP_CONFLICT);
        }

        $team->members()->attach($user->id, [
            'team_role_id' => $validated['team_role_id'],
        ]);

        return response()->json([
            'message' => 'Používateľ bol úspešne pridaný do tímu.',
            'team'    => $team->load('members'),
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
