<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Modules\Notifications\Emails\SendWelcomeToOrg;
use Modules\Organizations\Events\OrganizationApproved;

class SendWelcomeEmailToOrganization implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function handle(OrganizationApproved $event): void
    {
        $organization = $event->organization->load('users');

        $orgAdmin = $organization->users->first();

        if (!$orgAdmin) return;

        Mail::to($orgAdmin->email)->queue(
            new SendWelcomeToOrg($organization)
        );
    }
}
