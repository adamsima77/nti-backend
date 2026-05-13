<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Notifications\Models\EmailTemplate;

class PasswordChangeConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;
    public string $email;

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
        $languageId = request()->cookie('i18n_redirected', 'sk') === 'en' ? 2 : 1;

        $template = EmailTemplate::findBySlug('password_changed')
            ?->forLanguage($languageId);;

        return $this->subject($template?->subject ?? 'Security Alert')
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $template?->subject ?? '',
                'body_html' => $template?->render([
                        'userEmail' => $this->email,
                    ]) ?? '',
            ]);
    }
}
