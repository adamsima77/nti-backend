<?php

namespace Modules\Applications\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Applications\Models\Applications;
use App\Models\User;

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

    /**
     * Determine whether the user can view the application.
     */
    public function view(User $user, Applications $application): bool
    {
        return $user->id === $application->user_id;
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
    public function update(User $user, Applications $application): bool
    {
        return $user->id === $application->user_id;
    }

    /**
     * Determine whether the user can delete the application.
     */
    public function delete(User $user, Applications $application): bool
    {
        return $user->id === $application->user_id;
    }

    /**
     * Determine whether the user can approve the application.
     */
    public function approve(User $user, Applications $application): bool
    {
        return $user->hasRole('admin'); // Only admins can approve
    }

    /**
     * Determine whether the user can reject the application.
     */
    public function reject(User $user, Applications $application): bool
    {
        return $user->hasRole('admin'); // Only admins can reject
    }
}
