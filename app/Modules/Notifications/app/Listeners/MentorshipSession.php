<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Modules\Mentorship\Events\MentorSessionEvent;
use Modules\Notifications\Emails\MentorshipSessionMail;

class MentorshipSession implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(MentorSessionEvent $event): void
    {
        $mentorSession = $event->mentorSession;


        $mentorSession->load([
            'mentorship.application.team.members',
            'mentorship.application.call',
            'mentorship.mentor',
        ]);

        $team = $mentorSession->mentorship->application->team;

        if ($team && $team->members) {
            foreach ($team->members as $user) {

                if ($user) {
                    $fullName = $user->name . ' ' . $user->surname;

                    Mail::to($user->email)->send(new MentorshipSessionMail($mentorSession, $fullName));
                }
            }
        }
    }
}
