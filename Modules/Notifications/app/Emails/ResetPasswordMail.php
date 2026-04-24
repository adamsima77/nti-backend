<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\IdentityAccess\Models\User;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $token,
        public User $user
    ) {}

    public function build()
    {
        $url = config('app.frontend_url') . '/reset-password?token=' . $this->token
            . '&email=' . urlencode($this->user->email);

        return $this->subject('Reset your password')
            ->view('notifications::emails.reset-password')
            ->with([
                'url' => $url,
                'user' => $this->user,
            ]);
    }
}
