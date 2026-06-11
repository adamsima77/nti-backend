<?php

namespace Modules\Mentorship\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;
use Modules\IdentityAccess\Models\User;
use Modules\Mentorship\Models\Milestone;
use Modules\Mentorship\Models\Mentorship;
use Modules\Programs\Models\Call;

class CallMilestonePolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user, ?Call $call = null): bool
    {
        if ($call === null) {
            return false;
        }
        return $this->isOrgAdminOrPo($user, $call)
            || $this->isAssignedMentor($user, $call);
    }

    public function create(User $user, ?Call $call = null): bool
    {
        if ($call === null) {
            return false;
        }
        return $this->isOrgAdminOrPo($user, $call);
    }

    public function update(User $user, Milestone $milestone): bool
    {
        return $this->isOrgAdminOrPo($user, $milestone->call)
            || $this->isAssignedMentor($user, $milestone->call);
    }

    public function delete(User $user, Milestone $milestone): bool
    {
        return $this->isOrgAdminOrPo($user, $milestone->call);
    }

    private function isOrgAdminOrPo(User $user, Call $call): bool
    {
        return DB::table('user_organization')
            ->join('organization_role', 'user_organization.organization_role', '=', 'organization_role.id')
            ->where('user_organization.user_id', $user->id)
            ->where('user_organization.organization_id', $call->organization_id)
            ->whereIn('organization_role.name', ['org_admin', 'org_product_owner'])
            ->exists();
    }

    private function isAssignedMentor(User $user, Call $call): bool
    {
        return Mentorship::whereHas('application', fn ($q) => $q->where('call_id', $call->id))
            ->where('mentor_user_id', $user->id)
            ->exists();
    }
}
