<?php

namespace Modules\Evaluation\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;
use Modules\Evaluation\Models\EvaluationScore;

class EvaluationScorePolicy
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

    public function view(User $user, EvaluationScore $score): bool
    {
        if ($score->evaluation && $score->evaluation->commissionMember) {
            return $user->id === $score->evaluation->commissionMember->user_id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, EvaluationScore $score): bool
    {
        if ($score->evaluation && $score->evaluation->commissionMember) {
            return $user->id === $score->evaluation->commissionMember->user_id;
        }

        return false;
    }

    public function delete(User $user, EvaluationScore $score): bool
    {
        if ($score->evaluation && $score->evaluation->commissionMember) {
            return $user->id === $score->evaluation->commissionMember->user_id;
        }

        return false;
    }
}
