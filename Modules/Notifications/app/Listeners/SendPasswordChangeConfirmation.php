<?php

namespace Modules\Notifications\Listeners;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Modules\Notifications\Emails\PasswordChangeConfirmationMail;

class SendPasswordChangeConfirmation implements ShouldQueue
{
    public function handle($event): void {
        Mail::to($event->user->email)->send(new PasswordChangeConfirmationMail($event->user->email));
    }
}
