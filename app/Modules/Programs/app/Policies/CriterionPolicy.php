<?php

namespace Modules\Programs\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Models\Criterion;

class CriterionPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function view(User $user, Criterion $criterion): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function update(User $user, Criterion $criterion): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function delete(User $user, Criterion $criterion): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }
}
