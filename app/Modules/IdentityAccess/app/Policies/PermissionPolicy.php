<?php

namespace Modules\IdentityAccess\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\Permission;
use Modules\IdentityAccess\Models\User;

class PermissionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }
    

    public function view(User $user, Permission $permission): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function delete(User $user, Permission $permission): bool
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
