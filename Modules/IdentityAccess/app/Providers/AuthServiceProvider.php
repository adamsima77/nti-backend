<?php
namespace Modules\IdentityAccess\Providers;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\IdentityAccess\Models\Role;
use Modules\IdentityAccess\Models\Status;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Models\UserConsent;
use Modules\IdentityAccess\Policies\ConsentTypePolicy;
use Modules\IdentityAccess\Policies\RolePolicy;
use Modules\IdentityAccess\Policies\StatusPolicy;
use Modules\IdentityAccess\Policies\UserConsentPolicy;
use Modules\IdentityAccess\Policies\UserPolicy;


class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        ConsentTypePolicy::class => ConsentTypePolicy::class,
        Role::class => RolePolicy::class,
        Status::class => StatusPolicy::class,
        UserConsent::class => UserConsentPolicy::class,
        User::class  => UserPolicy::class
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
