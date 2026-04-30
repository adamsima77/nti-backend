<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Emails\WelcomeEmail;

class SendWelcomeEmail
{
    /**
     * Create the event listener.
     */
    public function __construct(User $user) {}

    /**
     * Handle the event.
     */
    public function handle($event): void {
        Mail::to($event->user->email)->send(new WelcomeEmail($event->user));
    }
}
