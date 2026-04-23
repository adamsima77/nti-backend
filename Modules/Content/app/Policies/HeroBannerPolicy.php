<?php

namespace Modules\Content\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Content\Models\HeroBanner;
use Modules\IdentityAccess\Models\User;

class HeroBannerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function fetchByLanguage(User $user): bool{
        return true;
    }

    public function view(User $user, HeroBanner $heroBanner): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin() || $user->isCMSEditor();
    }

    public function update(User $user, HeroBanner $heroBanner): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin() || $user->isCMSEditor();
    }

    public function delete(User $user, HeroBanner $heroBanner): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin() || $user->isCMSEditor();
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
