<?php

namespace Modules\Reporting\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Programs\Events\CallClosed;
use Modules\Reporting\Listeners\HandleCallClosure;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CallClosed::class => [
            HandleCallClosure::class,
        ],
    ];

    protected static $shouldDiscoverEvents = true;

    protected function configureEmailVerification(): void {}
}
