<?php

namespace Modules\Notifications\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Models\EmailTemplate;

class EmailTemplatePolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function fetchAll(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'content.view');
    }

    public function view(User $user, EmailTemplate $email): bool
    {
        return $this->hasPermission($user, 'content.view');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'content.create');
    }

    public function update(User $user, EmailTemplate $email): bool
    {
        return $this->hasPermission($user, 'content.edit_any');
    }

    public function delete(User $user, EmailTemplate $email): bool
    {
        return $this->hasPermission($user, 'content.delete_any');
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
