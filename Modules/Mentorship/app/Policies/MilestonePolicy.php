<?php

namespace Modules\Mentorship\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;
use Modules\Mentorship\Models\Milestone;

class MilestonePolicy
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
        return $this->hasPermission($user, 'mentorship.sessions.view_any');
    }

    public function view(User $user, Milestone $milestone): bool
    {
        return $this->hasPermission($user, 'mentorship.sessions.view_any')
            || $this->hasPermission($user, 'mentorship.sessions.view_own');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'mentorship.sessions.log');
    }

    public function update(User $user, Milestone $milestone): bool
    {
        return $this->hasPermission($user, 'mentorship.sessions.log')
            || $this->hasPermission($user, 'mentorship.sessions.approve');
    }

    public function delete(User $user, Milestone $milestone): bool
    {
        return $this->hasPermission($user, 'mentorship.sessions.approve');
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