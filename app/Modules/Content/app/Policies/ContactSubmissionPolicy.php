<?php

namespace Modules\Content\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Content\Models\ContactSubmission;
use Modules\IdentityAccess\Models\User;

class ContactSubmissionPolicy
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
        return $this->hasPermission($user, 'content.contact_submission.view');
    }

    public function view(User $user, ContactSubmission $contactSubmission): bool
    {
        return $this->hasPermission($user, 'content.contact_submission.view');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'content.contact_submission.create');
    }

    public function update(User $user, ContactSubmission $contactSubmission): bool
    {
        return $this->hasPermission($user, 'content.contact_submission.edit');
    }

    public function delete(User $user, ContactSubmission $contactSubmission): bool
    {
        return $this->hasPermission($user, 'content.contact_submission.delete');
    }

    public function restore(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user): bool
    {
        return false;
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
