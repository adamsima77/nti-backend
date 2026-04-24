<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerifyEmailMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $url,
        public $user
    ) {}

    public function build()
    {
        return $this->subject('Verify your email address')
            ->view('notifications::emails.verify-email')
            ->with([
                'url' => $this->url,
                'user' => $this->user,
            ]);
    }
}
