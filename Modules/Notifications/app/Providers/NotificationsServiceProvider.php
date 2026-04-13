<?php

namespace Modules\Notifications\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class NotificationsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Notifications';
    protected string $nameLower = 'notifications';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];
}
