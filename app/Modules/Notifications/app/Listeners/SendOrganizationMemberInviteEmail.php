<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Modules\Notifications\Emails\OrganizationMemberInviteMail;
use Modules\Organizations\Events\OrganizationMemberInvited;

class SendOrganizationMemberInviteEmail implements ShouldQueue
{
    public function handle(OrganizationMemberInvited $event): void
    {
        Mail::to($event->invitation->email)->send(new OrganizationMemberInviteMail(
            organizationName: $event->organization->name,
            roleLabel:        $event->roleLabel,
            inviteeEmail:     $event->invitation->email,
            token:            $event->invitation->token,
            lang:             $event->lang,
        ));
    }
}
