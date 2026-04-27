<?php

namespace Modules\Programs\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\Programs\Models\Program;
use Modules\Programs\Models\Programs;
use Modules\Programs\Policies\ProgramPolicy;

class ProgramsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Programs';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'programs';

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

    public function register(): void
    {
        parent::register();

        Gate::policy(Program::class, ProgramPolicy::class);
        Gate::policy(Programs::class, ProgramPolicy::class);
    }

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
