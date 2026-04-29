<?php

namespace Modules\Teams\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Teams\Models\Team;
use Modules\Teams\Policies\TeamPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class TeamsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Teams';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'teams';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Team::class, TeamPolicy::class);
    }
}
