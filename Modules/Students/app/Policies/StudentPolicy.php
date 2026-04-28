<?php

namespace Modules\Students\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;
use Modules\Students\Models\Student;

class StudentPolicy
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

    public function view(User $user, Student $student): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Student $student): bool
    {
        return $user->id === $student->user_id;
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->id === $student->user_id;
    }
}
