<?php

namespace Modules\Reporting\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;

class SuperAdminDashboardPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function fetchAllUsersCount(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function fetchLogs(User $user): bool{
        return $user->isSuperAdmin();
    }

    public function fetchStatusOfServices(User $user): bool{
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can fetch all organizations count.
     */
    public function fetchAllOrganizationsCount(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can fetch active errors and security alerts.
     */
    public function fetchActiveErrors(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can fetch GDPR prune statistics.
     */
    public function fetchGdprPrune(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
