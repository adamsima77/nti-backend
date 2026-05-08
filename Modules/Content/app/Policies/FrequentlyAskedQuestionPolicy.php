<?php

namespace Modules\Content\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Content\Models\FrequentlyAskedQuestion;
use Modules\IdentityAccess\Models\User;

class FrequentlyAskedQuestionPolicy
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

    public function fetchByLanguage(User $user): bool{
        return true;
    }

    public function view(User $user, FrequentlyAskedQuestion $faq): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'content.faq.create')
            || $user->isCMSEditor();
    }

    public function update(User $user, FrequentlyAskedQuestion $faq): bool
    {
        return $this->hasPermission($user, 'content.faq.edit')
            || $user->isCMSEditor();
    }

    public function delete(User $user, FrequentlyAskedQuestion $faq): bool
    {
        return $this->hasPermission($user, 'content.faq.delete')
            || $user->isCMSEditor();
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
