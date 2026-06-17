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
    public function __construct(string $email, public int $languageId) {
        $this->email = $email;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $template = EmailTemplate::findBySlug('password_changed')
            ?->forLanguage($this->languageId);

        $data = [
            'userEmail' => $this->email,
        ];

        $renderedSubject = $template ? $template->renderSubject($data) : 'Security Alert';
        $renderedBody    = $template ? $template->render($data) : '';

        return $this->subject($renderedSubject)
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $renderedSubject,
                'body_html' => $renderedBody,
            ]);
    }
}
