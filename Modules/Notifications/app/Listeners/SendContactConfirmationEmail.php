<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Modules\Content\Events\ContactMessageSubmitted;
use Modules\Notifications\Emails\ContactSubmissionMail;

class SendContactConfirmationEmail implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(ContactMessageSubmitted $event): void
    {
        Mail::to($event->submission->email)->send(
            new ContactSubmissionMail($event->submission)
        );
    }
}
