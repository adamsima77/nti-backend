<?php

namespace Modules\Reporting\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;
use Modules\Reporting\Models\ProjectOutput;
use Modules\Applications\Models\Application;

class ProjectOutputPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any outputs
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the output
     */
    public function view(User $user, ProjectOutput $output): bool
    {
        return $this->canManageOutput($user, $output);
    }

    /**
     * Determine whether the user can create outputs
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create output for a specific application
     */
    public function createForApplication(User $user, Application $application): bool
    {
        // User must be creator of the application or have admin/mentor role
        return $user->id === $application->created_by
            || $user->hasRole('admin')
            || $user->hasRole('mentor');
    }

    /**
     * Determine whether the user can update the output
     */
    public function update(User $user, ProjectOutput $output): bool
    {
        return $this->canManageOutput($user, $output);
    }

    /**
     * Determine whether the user can delete the output
     */
    public function delete(User $user, ProjectOutput $output): bool
    {
        return $this->canManageOutput($user, $output);
    }

    /**
     * Determine whether the user can mark the output as delivered
     */
    public function markAsDelivered(User $user, ProjectOutput $output): bool
    {
        return $this->canManageOutput($user, $output);
    }

    /**
     * Check if user can manage this output (view, update, delete)
     */
    private function canManageOutput(User $user, ProjectOutput $output): bool
    {
        // Admin can manage all outputs
        if ($user->hasRole('admin')) {
            return true;
        }

        // Mentor can manage outputs for their assigned projects
        if ($user->hasRole('mentor')) {
            $mentorship = $output->application->mentorships()
                ->where('mentor_id', $user->id)
                ->exists();

            return $mentorship;
        }

        // Creator of the application can manage outputs
        return $user->id === $output->application->created_by;
    }
}
