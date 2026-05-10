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
        public readonly ContactSubmission $submission
    ) {}

    public function build(): self
    {
        $template = EmailTemplate::findBySlug('contact_message_received');

        return $this->subject($template?->subject ?? 'We received your message')
            ->view('notifications::emails.layout')
            ->with([
                'subject' => $template?->subject ?? '',
                'body_html' => $template?->render([
                        'name' => $this->submission->name,
                        'description' => $this->submission->description,
                        'email' => $this->submission->email,
                    ]) ?? '',
            ]);
    }
}
