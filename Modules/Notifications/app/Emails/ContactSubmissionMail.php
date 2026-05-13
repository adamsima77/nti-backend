<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Blade;
use Modules\Content\Models\ContactSubmission;
use Modules\Notifications\Models\EmailTemplate;

class ContactSubmissionMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly ContactSubmission $submission,
        public int $languageId
    ) {}

    public function build(): self
    {
        $template = EmailTemplate::findBySlug('contact_message_received')
            ?->forLanguage($this->languageId);

        $data = [
            'name'        => $this->submission->name,
            'description' => $this->submission->description,
            'email'       => $this->submission->email,
        ];

        $renderedSubject = $template ? $template->renderSubject($data) : 'We received your message';
        $renderedBody    = $template ? $template->render($data) : '';

        return $this->subject($renderedSubject)
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $renderedSubject,
                'body_html' => $renderedBody,
            ]);
    }
}
