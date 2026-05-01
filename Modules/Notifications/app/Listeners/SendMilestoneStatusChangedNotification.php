<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Modules\Mentorship\Events\MilestoneStatusChanged;
use Modules\Notifications\Emails\MilestoneStatusChangedMail;

class SendMilestoneStatusChangedNotification implements ShouldQueue
{
    public function handle(MilestoneStatusChanged $event): void
    {
        $recipient = $event->milestone->application?->creator;

        if ($recipient === null) {
            return;
        }

        Mail::to($recipient->email)->send(new MilestoneStatusChangedMail(
            $event->milestone,
            $event->oldStatus,
            $event->newStatus,
            $event->changedBy,
        ));
    }
}
