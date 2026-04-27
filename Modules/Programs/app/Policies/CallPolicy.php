<?php

namespace Modules\Programs\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Models\Call;

class CallPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user = null): bool
    {
        return true;
    }

    public function view(?User $user = null, ?Call $call = null): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function update(User $user, Call $call): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function delete(User $user, Call $call): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }
}
