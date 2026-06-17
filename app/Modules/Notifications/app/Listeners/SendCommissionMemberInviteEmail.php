<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Modules\Evaluation\Events\CommissionMemberInvited;
use Modules\Notifications\Emails\CommissionMemberInviteMail;

class SendCommissionMemberInviteEmail implements ShouldQueue
{
    public function handle(CommissionMemberInvited $event): void
    {
        Mail::to($event->invitation->email)->send(new CommissionMemberInviteMail(
            commissionName: $event->commission->name,
            inviteeEmail:   $event->invitation->email,
            token:          $event->invitation->token,
            lang:           $event->lang,
        ));
    }
}
