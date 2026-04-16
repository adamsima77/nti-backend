<?php

namespace Modules\IdentityAccess\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;

class UserPolicy
{
    use HandlesAuthorization;
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin() || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin() || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin() || $user->id === $model->id;
    }

    public function restore(User $user, User $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
