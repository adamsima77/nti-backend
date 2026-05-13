<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Notifications\Models\EmailTemplate;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $url,
        public $user,
        public int $languageId
    ) {}

    public function build(): self
    {
        $template = EmailTemplate::findBySlug('verify_email')
            ?->forLanguage($this->languageId);

        $data = [
            'verificationUrl' => $this->url,
            'user'            => $this->user,
        ];

        $renderedSubject = $template ? $template->renderSubject($data) : 'Verify your email address';
        $renderedBody    = $template ? $template->render($data) : '';

        return $this->subject($renderedSubject)
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $renderedSubject,
                'body_html' => $renderedBody,
            ]);
    }
}
