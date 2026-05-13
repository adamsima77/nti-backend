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
    public function __construct(User $user) {
        $this->user = $user;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $languageId = request()->cookie('i18n_redirected', 'sk') === 'en' ? 2 : 1;

        $template = EmailTemplate::findBySlug('student_onboarded')
            ?->forLanguage($languageId);;

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
