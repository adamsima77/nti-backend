<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Modules\Content\Enums\LanguageType;
use Modules\Notifications\Emails\SendWelcomeToOrg;
use Modules\Organizations\Events\OrganizationApproved;

class SendWelcomeEmailToOrganization implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function handle(OrganizationApproved $event): void
    {
        $user = $event->user->load('organizations');
        $organization = $user->organizations->first();
        if (!$organization) {
            return;
        }

        $languageId = LanguageType::ENGLISH->value;

        Mail::to($user->email)->send(
            new SendWelcomeToOrg($organization, $languageId)
        );
    }
}
