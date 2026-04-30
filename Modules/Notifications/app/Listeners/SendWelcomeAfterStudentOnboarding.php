<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Modules\Notifications\Emails\StudentOnboardingEmail;

class SendWelcomeAfterStudentOnboarding
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle($event): void {
        $user = $event->user;
        Mail::to($user->email)->send(new StudentOnboardingEmail($user));
    }
}
