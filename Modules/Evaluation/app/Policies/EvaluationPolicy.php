<?php

namespace Modules\Evaluation\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;
use Modules\Evaluation\Models\Evaluation;

class EvaluationPolicy
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
        // Allow viewing evaluations list only for admins and commission members
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function view(User $user, Evaluation $evaluation): bool
    {
        // Allow viewing evaluation if user is admin or commission member who created it
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        if ($evaluation->commissionMember && $evaluation->commissionMember->user_id) {
            return $user->id === $evaluation->commissionMember->user_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Allow creation only for admin and commission members
        return $user->isAdmin() || $user->isSuperAdmin() || $this->isCommissionMember($user);
    }

    public function update(User $user, Evaluation $evaluation): bool
    {
        if ($evaluation->commissionMember && $evaluation->commissionMember->user_id) {
            return $user->id === $evaluation->commissionMember->user_id;
        }

        return false;
    }

    public function delete(User $user, Evaluation $evaluation): bool
    {
        if ($evaluation->commissionMember && $evaluation->commissionMember->user_id) {
            return $user->id === $evaluation->commissionMember->user_id;
        }

        return false;
    }

    /**
     * Check if user is a commission member.
     */
    private function isCommissionMember(User $user): bool
    {
        return \Modules\Evaluation\Models\CommissionMember::where('user_id', $user->id)->exists();
    }
}
