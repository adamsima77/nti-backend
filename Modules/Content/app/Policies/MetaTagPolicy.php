<?php

namespace Modules\Content\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Content\Models\MetaTag;
use Modules\IdentityAccess\Models\User;

class MetaTagPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function fetchByLanguage(User $user): bool{
        return true;
    }

    public function view(User $user, MetaTag $metaTag): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'content.meta_tag.create');
    }

    public function update(User $user, MetaTag $metaTag): bool
    {
        return $this->hasPermission($user, 'content.meta_tag.edit');
    }

    public function delete(User $user, MetaTag $metaTag): bool
    {
        return $this->hasPermission($user, 'content.meta_tag.delete');
    }

    public function restore(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user): bool
    {
        return false;
    }

    private function hasPermission(User $user, string $permission): bool
    {
        return $user->roles()
            ->whereHas('permissions', function ($query) use ($permission): void {
                $query->where('name', $permission);
            })
            ->exists();
    }
}
