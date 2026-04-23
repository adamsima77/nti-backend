<?php

namespace Modules\Content\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Content\Models\PartnerReference;
use Modules\IdentityAccess\Models\User;

class PartnerReferencePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function fetchByLanguage(User $user): bool{
        return true;
    }

    public function view(User $user, PartnerReference $partnerReference): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin() || $user->isCMSEditor();
    }

    public function update(User $user, PartnerReference $partnerReference): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin() || $user->isCMSEditor();
    }

    public function delete(User $user, PartnerReference $partnerReference): bool
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
