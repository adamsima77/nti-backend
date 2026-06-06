<?php

namespace Modules\Applications\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Applications\Models\Application;
use Modules\IdentityAccess\Models\User;

class ApplicationsPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any applications.
     */
    public function viewAny(User $user): bool
    {
        return true; // Allow all authenticated users to view applications
    }

     public function addCommittee(User $user, Application $application): bool{
        return $user->isSuperAdmin() || $user->isAdmin();
     }

    /**
     * Determine whether the user can view the application.
     */
    public function view(User $user, Application $application): bool
    {
        return $user->id === $application->created_by || $user->isAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can create applications.
     */
    public function create(User $user): bool
    {
        return true; // Allow all authenticated users to create applications
    }

    /**
     * Determine whether the user can update the application.
     */
    public function update(User $user, Application $application): bool
    {
        return $user->id === $application->created_by || $user->isAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can delete the application.
     */
    public function delete(User $user, Application $application): bool
    {
        return $user->id === $application->created_by || $user->isAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can approve the application.
     */
    public function approve(User $user, Application $application): bool
    {
        return $user->hasRole('admin'); // Only admins can approve
    }

    /**
     * Determine whether the user can reject the application.
     */
    public function reject(User $user, Application $application): bool
    {
        return $user->hasRole('admin'); // Only admins can reject
    }

    public function changeStatus(User $user, Application $application): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin() || $user->isCommissionChair();
    }

    public function export(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }
}
