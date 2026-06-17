<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Notifications\Models\EmailTemplate;

class OrganizationMemberInviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $organizationName,
        public string $roleLabel,
        public string $inviteeEmail,
        public string $token,
        public string $lang = 'sk',
    ) {}

    private function acceptUrl(): string
    {
        $base = rtrim((string) config('app.frontend_url'), '/');
        $path = $this->lang === 'en'
            ? '/en/auth/accept-invite'
            : '/auth/accept-invite';

        return $base.$path.'?token='.rawurlencode($this->token);
    }

    public function build(): self
    {
        $languageId = $this->lang === 'en' ? 2 : 1;

        $template = EmailTemplate::findBySlug('organization_member_invite')
            ?->forLanguage($languageId);

        $acceptUrl = $this->acceptUrl();

        $vars = [
            'organizationName' => $this->organizationName,
            'roleLabel'        => $this->roleLabel,
            'acceptUrl'        => $acceptUrl,
            'inviteeEmail'     => $this->inviteeEmail,
        ];

        $subject = $template
            ? $template->renderSubject($vars)
            : ($this->lang === 'en'
                ? 'You have been invited to join '.$this->organizationName.' on NTI'
                : 'Organizácia '.$this->organizationName.' vás pozýva na NTI platformu');

        $bodyHtml = $template
            ? $template->render($vars)
            : $this->fallbackHtml($acceptUrl);

        return $this->subject($subject)
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $subject,
                'body_html' => $bodyHtml,
            ]);
    }
}
