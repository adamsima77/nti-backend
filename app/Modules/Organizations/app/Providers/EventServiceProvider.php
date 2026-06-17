<?php

namespace Modules\Organizations\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Notifications\Listeners\SendOrganizationMemberInviteEmail;
use Modules\Organizations\Events\OrganizationMemberInvited;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrganizationMemberInvited::class => [
            SendOrganizationMemberInviteEmail::class,
        ],
    ];

    protected static $shouldDiscoverEvents = true;

    protected function configureEmailVerification(): void {}
}
