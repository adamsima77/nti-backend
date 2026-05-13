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
        $languageId = request()->cookie('i18n_redirected', 'sk') === 'en' ? 2 : 1;

        $template = EmailTemplate::findBySlug('contact_message_received')
            ?->forLanguage($languageId);;

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
