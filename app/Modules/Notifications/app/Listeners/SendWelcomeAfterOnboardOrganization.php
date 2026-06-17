<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Emails\OrganizationOnboardingEmail;

class SendWelcomeAfterOnboardOrganization implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle($event): void {
        $org = $event->organization;
        $email = $event->email;
        Mail::to($email)->send(new OrganizationOnboardingEmail($org, $email, $event->languageId));
    }
}
