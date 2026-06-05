<?php

namespace Modules\Notifications\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Models\Notifications;

class NotificationPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function sendBulkEmail(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }
    public function viewAny(User $user): bool
    {

        return true;
    }

    public function markAllRead(User $user): bool
    {
        return true;
    }

    public function markRead(User $user, Notifications $notification): bool
    {
        return $user->id === $notification->user_id;
    }

    /**
     * Determine if the user can update a specific notification.
     * Tied to: markRead()
     */
    public function update(User $user, Notifications $notification): bool
    {
        return $user->id === $notification->user_id;
    }
}
