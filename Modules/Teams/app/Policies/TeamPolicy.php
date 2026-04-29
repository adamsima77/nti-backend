<?php

namespace Modules\Teams\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;
use Modules\Teams\Models\Team;
use Modules\Teams\Models\TeamRole;

class TeamPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Team $team): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Team $team): bool
    {
        return $this->isTeamLeader($user, $team);
    }

    public function delete(User $user, Team $team): bool
    {
        return $this->isTeamLeader($user, $team);
    }

    public function addMember(User $user, Team $team): bool
    {
        return $this->isTeamLeader($user, $team);
    }

    public function removeMember(User $user, Team $team): bool
    {
        return $this->isTeamLeader($user, $team);
    }

    private function isTeamLeader(User $user, Team $team): bool
    {
        $teamleader = TeamRole::where('name', 'Vedúci tímu')->first();

        if (!$teamleader) {
            return false;
        }

        return $team->members()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('team_role_id', $teamleader->id)
            ->exists();
    }
}
