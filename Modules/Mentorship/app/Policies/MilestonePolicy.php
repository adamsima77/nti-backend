<?php

namespace Modules\Mentorship\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Applications\Models\Application;
use Modules\IdentityAccess\Models\User;
use Modules\Mentorship\Models\Milestone;

class MilestonePolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function fetchForStudent(User $user): bool
    {
        return $user->isStudent();
    }

    public function studentAnswer(User $user, Milestone $milestone): bool
    {
        if (!$user->isStudent()) {
            return false;
        }

        if (!in_array($milestone->status, [2, 6])) {// V rieseni/Vratenie na doplnenie
            return false;
        }
        $application = Application::where('call_id', $milestone->call_id)
            ->whereHas('team.members', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        return !($application == null);
    }

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'mentorship.sessions.view_any');
    }

    public function view(User $user, Milestone $milestone): bool
    {
        return $this->hasPermission($user, 'mentorship.sessions.view_any')
            || $this->hasPermission($user, 'mentorship.sessions.view_own');
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'mentorship.sessions.log');
    }

    public function update(User $user, Milestone $milestone): bool
    {
        return $this->hasPermission($user, 'mentorship.sessions.log')
            || $this->hasPermission($user, 'mentorship.sessions.approve');
    }

    public function delete(User $user, Milestone $milestone): bool
    {
        return $this->hasPermission($user, 'mentorship.sessions.approve');
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
