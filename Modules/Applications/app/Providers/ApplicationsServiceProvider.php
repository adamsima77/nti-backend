<?php

namespace Modules\Applications\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
// Changed to the proper Facade import:
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Gate;
use Nwidart\Modules\Support\ModuleServiceProvider;

use Modules\Applications\Models\Application;
use Modules\Applications\Models\Applications;
use Modules\Applications\Models\Document;
use Modules\Applications\Models\StatusOfApplication;

use Modules\Applications\Observers\ApplicationObserver;

use Modules\Applications\Policies\ApplicationsPolicy;
use Modules\Applications\Policies\DocumentPolicy;
use Modules\Applications\Policies\StatusOfApplicationPolicy;

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

        $this->registerRateLimiter();

        Application::observe(ApplicationObserver::class);

        $this->loadViewsFrom(module_path($this->name, '/resources/views'), $this->nameLower);
    }

    public function registerRateLimiter(): void
    {

        RateLimiter::for('application', function (Request $request) {

            $userId = $request->user()?->id ?? 'guest';

            $key = sha1($userId . '|' . $request->ip());

            return [
                Limit::perMinute(50)->by($key),
            ];
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        parent::register();

        // Register the policies
        Gate::policy(Application::class, ApplicationsPolicy::class);
        Gate::policy(Applications::class, ApplicationsPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(StatusOfApplication::class, StatusOfApplicationPolicy::class);
    }
}
