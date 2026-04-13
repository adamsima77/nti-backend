<?php

namespace Modules\Organizations\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class OrganizationsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Organizations';
    protected string $nameLower = 'organizations';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
