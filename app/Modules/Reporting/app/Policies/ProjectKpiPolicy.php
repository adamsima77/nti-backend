<?php

namespace Modules\Reporting\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\IdentityAccess\Models\User;
use Modules\Reporting\Models\ProjectKpi;
use Modules\Applications\Models\Application;

class ProjectKpiPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any KPIs
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the KPI
     */
    public function view(User $user, ProjectKpi $kpi): bool
    {
        return $this->canManageKpi($user, $kpi);
    }

    /**
     * Determine whether the user can create KPIs
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create KPI for a specific application
     */
    public function createForApplication(User $user, Application $application): bool
    {
        // User must be creator of the application or have admin/mentor role
        return $user->id === $application->created_by
            || $user->hasRole('admin')
            || $user->hasRole('mentor');
    }

    /**
     * Determine whether the user can update the KPI
     */
    public function update(User $user, ProjectKpi $kpi): bool
    {
        return $this->canManageKpi($user, $kpi);
    }

    /**
     * Determine whether the user can delete the KPI
     */
    public function delete(User $user, ProjectKpi $kpi): bool
    {
        return $this->canManageKpi($user, $kpi);
    }

    /**
     * Check if user can manage this KPI (view, update, delete)
     */
    private function canManageKpi(User $user, ProjectKpi $kpi): bool
    {
        // Admin can manage all KPIs
        if ($user->hasRole('admin')) {
            return true;
        }

        // Mentor can manage KPIs for their assigned projects
        if ($user->hasRole('mentor')) {
            $mentorship = $kpi->application->mentorships()
                ->where('mentor_id', $user->id)
                ->exists();

            return $mentorship;
        }

        // Creator of the application can manage KPIs
        return $user->id === $kpi->application->created_by;
    }
}
