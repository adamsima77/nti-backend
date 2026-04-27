<?php

namespace Modules\Organizations\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;
use Modules\Organizations\Models\Organization;

class OrganizationPolicy
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

    public function view(User $user, Organization $organization): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->isOrganizationAdmin($user, $organization);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $this->isOrganizationAdmin($user, $organization);
    }

    private function isOrganizationAdmin(User $user, Organization $organization): bool
    {
        return $user->organizations()
            ->wherePivot('organization_id', $organization->id)
            ->wherePivot('organization_role', function ($query) {
                $query->select('id')
                    ->from('organization_role')
                    ->where('name', 'admin');
            })
            ->exists();
    }
}
