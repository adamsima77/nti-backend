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
        return true;
    }

    public function view(User $user, Evaluation $evaluation): bool
    {
        if ($evaluation->commissionMember && $evaluation->commissionMember->user_id) {
            return $user->id === $evaluation->commissionMember->user_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return true;
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
}
