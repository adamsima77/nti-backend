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
        public $user
    ) {}

    public function build(): self
    {
        $template = EmailTemplate::findBySlug('verify_email');

        return $this->subject($template?->subject ?? 'Verify your email address')
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $template?->subject ?? '',
                'body_html' => $template?->render([
                        'verificationUrl' => $this->verificationUrl,
                        'user'            => $this->user,
                    ]) ?? '',
            ]);
    }
}
