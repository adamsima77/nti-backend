<?php

namespace Modules\Programs\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Models\Program;

class ProgramPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function view(User $user, Program $program): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function update(User $user, Program $program): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function delete(User $user, Program $program): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function restore(User $user, Program $program): bool
    {
        return false;
    }

    public function forceDelete(User $user, Program $program): bool
    {
        return false;
    }
}
