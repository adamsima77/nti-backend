<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\IdentityAccess\Models\User;

class StudentOnboardingEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    private User $user;
    public function __construct(User $user) {
        $this->user = $user;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->view('notifications::emails.student-onboarded')
            ->with([
                'userName' => $this->user->name . " " . $this->user->surname
            ]);
    }
}
