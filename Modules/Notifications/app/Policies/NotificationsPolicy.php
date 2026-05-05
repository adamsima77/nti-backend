<?php

namespace Modules\Notifications\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Models\Notifications;

class NotificationsPolicy
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
        return $this->hasPermission($user, 'notifications.view_own');
    }

    public function view(User $user, Notifications $notifications): bool
    {
        return $this->hasPermission($user, 'notifications.view_own');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'notifications.send');
    }

    public function update(User $user, Notifications $notifications): bool
    {
        return $this->hasPermission($user, 'notifications.manage_own')
            || $this->hasPermission($user, 'notifications.manage_templates');
    }

    public function delete(User $user, Notifications $notifications): bool
    {
        return $this->hasPermission($user, 'notifications.manage_own')
            || $this->hasPermission($user, 'notifications.manage_templates');
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