<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Models\EmailTemplate;

class StudentOnboardingEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public User $user;
    public function __construct(User $user, public int $languageId) {
        $this->user = $user;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $template = EmailTemplate::findBySlug('student_onboarded')
            ?->forLanguage($this->languageId);

        $data = [
            'userName' => $this->user->name . ' ' . $this->user->surname,
        ];

        $renderedSubject = $template ? $template->renderSubject($data) : 'Welcome to NTI!';
        $renderedBody    = $template ? $template->render($data) : '';

        return $this->subject($renderedSubject)
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $renderedSubject,
                'body_html' => $renderedBody,
            ]);
    }
}
