<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\IdentityAccess\Models\User;

class WelcomeEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private User $user;

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function build(): self
    {
        return $this->view('notifications::emails.welcome-email')->with([
            'userName' => $this->user->name . ' ' . $this->user->surname,
        ]);
    }
}
