<?php

namespace Modules\Notifications\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\IdentityAccess\Events\OrganizationOnboarded;
use Modules\IdentityAccess\Events\PasswordChanged;
use Modules\IdentityAccess\Events\PasswordResetRequested;
use Modules\IdentityAccess\Events\StudentOnboarded;
use Modules\IdentityAccess\Events\UserRegistered;
use Modules\Notifications\Listeners\SendPasswordChangeConfirmation;
use Modules\Notifications\Listeners\SendPasswordResetEmail;
use Modules\Notifications\Listeners\SendWelcomeAfterOnboardOrganization;
use Modules\Notifications\Listeners\SendWelcomeAfterStudentOnboarding;
use Modules\Notifications\Listeners\SendWelcomeEmail;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PasswordChanged::class => [
            SendPasswordChangeConfirmation::class,
        ],

        PasswordResetRequested::class => [
            SendPasswordResetEmail::class,
        ],

        UserRegistered::class => [
            SendWelcomeEmail::class,

            OrganizationOnboarded::class =>[
                SendWelcomeAfterOnboardOrganization::class
            ],

            StudentOnboarded::class => [
                SendWelcomeAfterStudentOnboarding::class
            ]
        ]
    ];
    protected static $shouldDiscoverEvents = true;

    protected function configureEmailVerification(): void {}
}
