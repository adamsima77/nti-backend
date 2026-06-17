<?php

namespace Modules\Evaluation\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;
use Modules\Evaluation\Models\CommissionMember;

class CommissionMemberPolicy
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

    public function view(User $user, CommissionMember $member): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CommissionMember $member): bool
    {
        return $user->id === $member->user_id;
    }

    public function delete(User $user, CommissionMember $member): bool
    {
        return $user->id === $member->user_id;
    }
}
