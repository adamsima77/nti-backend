<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Notifications\Models\EmailTemplate;

class CommissionMemberInviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $commissionName,
        public string $inviteeEmail,
        public string $token,
        public string $lang = 'sk',
    ) {}

    private function acceptUrl(): string
    {
        $base = rtrim((string) config('app.frontend_url'), '/');
        $path = $this->lang === 'en'
            ? '/en/auth/accept-commission-invite'
            : '/auth/accept-commission-invite';

        return $base . $path . '?token=' . rawurlencode($this->token);
    }

    public function build(): self
    {
        $languageId = $this->lang === 'en' ? 2 : 1;

        $template = EmailTemplate::findBySlug('commission_member_invite')
            ?->forLanguage($languageId);

        $acceptUrl = $this->acceptUrl();

        $vars = [
            'commissionName' => $this->commissionName,
            'acceptUrl'      => $acceptUrl,
            'inviteeEmail'   => $this->inviteeEmail,
        ];

        $subject = $template
            ? $template->renderSubject($vars)
            : ($this->lang === 'en'
                ? 'You have been invited to join the evaluation commission on NTI'
                : 'Boli ste pozvaní do hodnotiacej komisie NTI');

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

    private function fallbackHtml(string $acceptUrl): string
    {
        return '<p>Boli ste pozvaní ako hodnotiteľ do komisie <strong>' . e($this->commissionName) . '</strong> na platforme NTI.</p>'
            . '<p><a href="' . $acceptUrl . '">Kliknite sem pre aktiváciu účtu</a></p>'
            . '<p>Odkaz platí 72 hodín.</p>';
    }
}
