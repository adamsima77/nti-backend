<?php

namespace Modules\Applications\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class ApplicationsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Applications';
    protected string $nameLower = 'applications';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
