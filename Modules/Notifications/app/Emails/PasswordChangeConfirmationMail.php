<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordChangeConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    private string $email;

    /**
     * Create a new message instance.
     */
    public function __construct(string $email) {
        $this->email = $email;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->view('notifications::emails.password-changed')->with([
            'userEmail' => $this->email,
            ]);
    }
}
