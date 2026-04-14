<?php

namespace Modules\Applications\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\Applications\Models\Applications;
use Modules\Applications\Policies\ApplicationsPolicy;

class ApplicationsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Applications';
    protected string $nameLower = 'applications';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        parent::register();

        // Register the policy
        Gate::policy(Applications::class, ApplicationsPolicy::class);
    }
}
