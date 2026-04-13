<?php

namespace Modules\Content\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class ContentServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Content';
    protected string $nameLower = 'content';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
