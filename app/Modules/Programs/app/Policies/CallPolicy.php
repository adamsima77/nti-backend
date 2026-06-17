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
        return $user->isAdmin() || $user->isSuperAdmin() || $user->isPartner();
    }

    public function update(User $user, Call $call): bool
    {
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        if ($user->isPartner()) {
            $isOwner = $user->organizations()
                ->where('organization_id', $call->organization_id)
                ->exists();
            $currentStatus = $call->currentStatusHistory?->status?->name;

            return $isOwner && in_array($currentStatus, ['Draft', 'Čaká na schválenie']);
        }

        return false;
    }

    public function delete(User $user, Call $call): bool
    {
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        if ($user->isPartner()) {
            $isOwner = $user->organizations()
                ->where('organization_id', $call->organization_id)
                ->exists();
            $currentStatus = $call->currentStatusHistory?->status?->name;

            return $isOwner && in_array($currentStatus, ['Draft', 'Čaká na schválenie']);
        }

        return false;
    }

    public function pdf(User $user, Call $call): bool
    {
        return true;
    }

    public function export(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function transition(User $user, Call $call): bool
    {
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        if ($user->isPartner()) {
            $currentStatus = $call->currentStatusHistory?->status?->name;
            $isOwner = $user->organizations()
                ->where('organization_id', $call->organization_id)
                ->exists();
            return $isOwner && in_array($currentStatus, ['Draft', 'Čaká na schválenie']);
        }

        return false;
    }
}
