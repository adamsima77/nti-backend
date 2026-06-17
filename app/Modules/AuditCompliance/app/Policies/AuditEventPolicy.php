<?php

namespace Modules\AuditCompliance\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\AuditCompliance\Models\AuditCompliance;
use Modules\IdentityAccess\Models\User;

class AuditEventPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    public function view(User $user, AuditCompliance $auditEvent): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin();
    }
}
