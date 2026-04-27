<?php

namespace Modules\Content\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Content\Models\SiteMember;
use Modules\IdentityAccess\Models\User;

class SiteMemberPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function fetchByLanguage(User $user): bool{
        return true;
    }

    public function view(User $user, SiteMember $member): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function update(User $user, SiteMember $member): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function delete(User $user, SiteMember $member): bool
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
