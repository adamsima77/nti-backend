<?php

namespace Modules\IdentityAccess\Observers;

use Illuminate\Support\Facades\Log;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Events\UserBanned;
use Modules\IdentityAccess\Models\User;
use Modules\Organizations\Events\OrganizationApproved;

class UserObserver
{
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        Log::info('UserObserver triggered for User ID: ' . $user->id);

        // Fallback robust check: look at what the value was before the save operation happened
        $originalStatus = (int) $user->getOriginal('status_id');
        $currentStatus  = (int) $user->status_id;

        if ($originalStatus !== $currentStatus) {
            Log::info("Status ID changed from {$originalStatus} to {$currentStatus}");

            // 1. Condition: State changed to Banned
            if ($currentStatus === UserStatus::BANNED->value) {
                Log::info('Dispatching UserBanned Event');
                event(new UserBanned($user));
            }

            // 2. Condition: User is an organization and state changed to Active
            if ($currentStatus === UserStatus::ACTIVE->value && $user->isPartner()) {
                Log::info('Dispatching OrganizationApproved Event');
                event(new OrganizationApproved($user));
            }
        }
    }
}
