<?php

namespace Modules\IdentityAccess\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\ConsentType;
use Modules\IdentityAccess\Models\User;

class ConsentTypePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function view(User $user, ConsentType $consentType): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function update(User $user, ConsentType $consentType): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function delete(User $user, ConsentType $consentType): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
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
