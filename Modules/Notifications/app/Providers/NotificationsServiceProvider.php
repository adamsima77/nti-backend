<?php

namespace Modules\Notifications\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Notifications\Models\Notifications;
use Modules\Notifications\Policies\NotificationsPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class NotificationsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Notifications';
    protected string $nameLower = 'notifications';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->loadViewsFrom(module_path($this->name, '/Resources/views'), $this->nameLower);
        Gate::policy(Notifications::class, NotificationsPolicy::class);
    }
}
