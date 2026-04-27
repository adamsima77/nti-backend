<?php

namespace Modules\Organizations\Providers;

use Modules\Organizations\Models\Organization;
use Modules\Organizations\Policies\OrganizationPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;

class OrganizationsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Organizations';
    protected string $nameLower = 'organizations';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Organization::class, OrganizationPolicy::class);
    }
}
