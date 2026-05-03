<?php

namespace Modules\Reporting\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Reporting\Models\ProjectKpi;
use Modules\Reporting\Models\ProjectOutput;
use Modules\Reporting\Policies\ProjectKpiPolicy;
use Modules\Reporting\Policies\ProjectOutputPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        ProjectKpi::class => ProjectKpiPolicy::class,
        ProjectOutput::class => ProjectOutputPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
