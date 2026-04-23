<?php

namespace Modules\Content\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Content\Models\FrequentlyAskedQuestion;
use Modules\IdentityAccess\Models\User;

class FrequentlyAskedQuestionPolicy
{
    use HandlesAuthorization;

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
        return $user->isAdmin() || $user->isSuperAdmin() || $user->isCMSEditor();
    }

    public function update(User $user, FrequentlyAskedQuestion $faq): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin() || $user->isCMSEditor();
    }

    public function delete(User $user, FrequentlyAskedQuestion $faq): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin() || $user->isCMSEditor();
    }

    public function restore(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user): bool
    {
        return false;
    }
}
