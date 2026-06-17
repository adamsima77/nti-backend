<?php

namespace Modules\Applications\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Applications\Models\StatusOfApplication;
use Modules\IdentityAccess\Models\User;
use Modules\Teams\Models\Team;

class StatusOfApplicationPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function __construct() {}

    public function viewAny(User $user){
        return $user->isSuperAdmin() || $user->isAdmin();
    }
}
