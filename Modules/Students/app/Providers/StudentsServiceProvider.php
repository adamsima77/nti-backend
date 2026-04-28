<?php

namespace Modules\Students\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Students\Models\Student;
use Modules\Students\Policies\StudentPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class StudentsServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Students';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'students';

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

        Gate::policy(Student::class, StudentPolicy::class);
    }
}
