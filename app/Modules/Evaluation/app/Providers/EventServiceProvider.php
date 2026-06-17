<?php

namespace Modules\Evaluation\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use Modules\Evaluation\Events\CommissionMemberInvited;
use Modules\Notifications\Listeners\SendCommissionMemberInviteEmail;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CommissionMemberInvited::class => [
            SendCommissionMemberInviteEmail::class,
        ],
    ];

    protected static $shouldDiscoverEvents = true;

    protected function configureEmailVerification(): void {}
}
