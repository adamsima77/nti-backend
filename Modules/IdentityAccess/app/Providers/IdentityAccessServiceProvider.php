<?php

namespace Modules\IdentityAccess\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Policies\UserPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class IdentityAccessServiceProvider extends ModuleServiceProvider
{

    public function boot(): void{
        Gate::policy(User::class, UserPolicy::class);
    }
    /**
     * The name of the module.
     */
    protected string $name = 'IdentityAccess';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'identityaccess';

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

    /**
     * Define module schedules.
     *
     * @param $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
