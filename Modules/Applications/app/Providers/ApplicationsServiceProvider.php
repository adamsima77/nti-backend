<?php

namespace Modules\Applications\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\Applications\Models\Applications;
use Modules\Applications\Models\Document;
use Modules\Applications\Policies\ApplicationsPolicy;
use Modules\Applications\Policies\DocumentPolicy;

class ApplicationsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Applications';
    protected string $nameLower = 'applications';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->loadViewsFrom(module_path($this->name, '/resources/views'), $this->nameLower);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        parent::register();

        // Register the policies
        Gate::policy(Applications::class, ApplicationsPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
    }
}
