<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Models\EmailTemplate;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function build(): self
    {
        $template = EmailTemplate::findBySlug('welcome_email');

        return $this->subject($template?->subject ?? 'Welcome to NTI!')
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $template?->subject ?? '',
                'body_html' => $template?->render([
                        'userName' => $this->user->name . ' ' . $this->user->surname,
                    ]) ?? '',
            ]);
    }
}
