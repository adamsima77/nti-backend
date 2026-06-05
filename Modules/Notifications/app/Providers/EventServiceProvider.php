<?php

namespace Modules\Notifications\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Content\Events\ContactMessageSubmitted;
use Modules\IdentityAccess\Events\OrganizationOnboarded;
use Modules\IdentityAccess\Events\PasswordChanged;
use Modules\IdentityAccess\Events\PasswordResetRequested;
use Modules\IdentityAccess\Events\StudentOnboarded;
use Modules\IdentityAccess\Events\UserBanned;
use Modules\IdentityAccess\Events\UserRegistered;
use Modules\Mentorship\Events\MilestoneStatusChanged;
use Modules\Notifications\Events\BulkEmail;
use Modules\Notifications\Listeners\SendBannedEmail;
use Modules\Notifications\Listeners\SendBulkEmail;
use Modules\Notifications\Listeners\SendContactConfirmationEmail;
use Modules\Notifications\Listeners\SendEmailToAdminWhenOrganizationOnboarded;
use Modules\Notifications\Listeners\SendPasswordChangeConfirmation;
use Modules\Notifications\Listeners\SendMilestoneStatusChangedNotification;
use Modules\Notifications\Listeners\SendPasswordResetEmail;
use Modules\Notifications\Listeners\SendWelcomeAfterOnboardOrganization;
use Modules\Notifications\Listeners\SendWelcomeAfterStudentOnboarding;
use Modules\Notifications\Listeners\SendWelcomeEmail;
use Modules\Notifications\Listeners\SendWelcomeEmailToOrganization;
use Modules\Organizations\Events\OrganizationApproved;

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
        ],

      BulkEmail::class => [
          SendBulkEmail::class
      ],

        OrganizationOnboarded::class => [
            SendWelcomeAfterOnboardOrganization::class,
            SendEmailToAdminWhenOrganizationOnboarded::class
        ],

         OrganizationApproved::class => [
           SendWelcomeEmailToOrganization::class,
         ],

      UserBanned::class => [
          SendBannedEmail::class,
      ],

        StudentOnboarded::class => [
            SendWelcomeAfterStudentOnboarding::class,
        ],

        MilestoneStatusChanged::class => [
            SendMilestoneStatusChangedNotification::class,
        ],

      ContactMessageSubmitted::class => [
          SendContactConfirmationEmail::class,
      ],
    ];
    protected static $shouldDiscoverEvents = true;

    protected function configureEmailVerification(): void {}
}
