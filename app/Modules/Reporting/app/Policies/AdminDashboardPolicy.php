<?php

namespace Modules\Reporting\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;

class AdminDashboardPolicy
{
    use HandlesAuthorization;

    private function isAdmin(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function fetchApplicationsCount(User $user) { return $this->isAdmin($user); }
    public function fetchActiveCallsCount(User $user)  { return $this->isAdmin($user); }
    public function fetchUsersCount(User $user)        { return $this->isAdmin($user); }
    public function fetchTeamCount(User $user)         { return $this->isAdmin($user); }
    public function fetchActiveCalls(User $user)      { return $this->isAdmin($user); }
    public function fetchPendingApprovalOrganizations(User $user)
    {
        return $this->isAdmin($user);
    }
}
