<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Models\EmailTemplate;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $token,
        public User $user,
        public int $languageId
    ) {}

    public function build(): self
    {
        $url = config('app.frontend_url') . '/auth/reset-password?token=' . $this->token
            . '&email=' . urlencode($this->user->email);

        $template = EmailTemplate::findBySlug('reset_password')
            ?->forLanguage($this->languageId);

        return $this->subject($template?->subject ?? 'Reset your password')
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $template?->subject ?? '',
                'body_html' => $template?->render([
                        'url'  => $url,
                        'userName' => $this->user->name,
                    ]) ?? '',
            ]);
    }
}
