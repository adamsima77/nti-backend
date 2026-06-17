<?php

namespace Modules\Mentorship\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;
use Modules\Mentorship\Models\Mentorship;
use Modules\Mentorship\Models\Milestone;

class MentorshipPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function fetchMentorCalls(User $user): bool
    {
        return $this->hasPermission($user, 'mentorship.view_any');
    }
    public function assignMentor(User $user): bool
    {
        return $this->hasPermission($user, 'mentorship.assign');
    }

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'mentorship.view_any');
    }

    public function view(User $user, Mentorship $mentorship): bool
    {
        return $this->hasPermission($user, 'mentorship.view_any')
            || $this->hasPermission($user, 'mentorship.view_own');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'mentorship.request')
            || $this->hasPermission($user, 'mentorship.assign');
    }

    public function update(User $user, Mentorship $mentorship): bool
    {
        return $this->hasPermission($user, 'mentorship.edit_any')
            || $this->hasPermission($user, 'mentorship.edit_own');
    }



    public function delete(User $user, Mentorship $mentorship): bool
    {
        return $this->hasPermission($user, 'mentorship.edit_any');
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
