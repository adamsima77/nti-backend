<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Models\EmailTemplate;

class SendUserBannedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly User $user,
        public int $languageId
    ) {}

    /**
     * Build the message.
     */
    public function build(): self
    {
        // Fetch dynamic template from DB
        $template = EmailTemplate::findBySlug('user_account_banned')
            ?->forLanguage($this->languageId);

        $data = [
            'userName'    => $this->user->name ?? 'User',
            'userSurname' => $this->user->surname ?? '',
        ];

        $renderedSubject = $template
            ? $template->renderSubject($data)
            : 'Important update regarding your NTI account';

        $renderedBody = $template
            ? $template->render($data)
            : '<p>Your account has been suspended by administration. Please contact support.</p>';

        return $this->subject($renderedSubject)
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $renderedSubject,
                'body_html' => $renderedBody,
            ]);
    }
}
