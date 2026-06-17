<?php

namespace Modules\IdentityAccess\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Models\UserConsent;

class UserConsentPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function view(User $user, UserConsent $userConsent): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin() || $user->id === $userConsent->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, UserConsent $userConsent): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin() || $user->id === $userConsent->user_id;
    }

    public function delete(User $user, UserConsent $userConsent): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin() || $user->id === $userConsent->user_id;
    }

    public function restore(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user): bool
    {
        return false;
    }
}
