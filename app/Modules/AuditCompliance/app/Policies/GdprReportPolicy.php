<?php

namespace Modules\AuditCompliance\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\AuditCompliance\Models\GdprReport;
use Modules\IdentityAccess\Models\User;

class GdprReportPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function __construct() {}

    /**
     * Determine if the user can view the list of GDPR reports.
     */
    public function view(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determine if the user can trigger a GDPR report generation.
     */
    public function generate(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    /**
     * Determine if the user can download a specific generated GDPR report.
     */
    public function download(User $user, GdprReport $report): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }
}
