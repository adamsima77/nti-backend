<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Notifications\Models\EmailTemplate;
use Modules\Teams\Models\Team;

class TeamInviteMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Team $team,
        public string $inviterDisplayName,
        public string $inviteeEmail,
        public string $token,
        public string $lang = 'sk',
    ) {}

    private function inviteUrl(): string
    {
        $base = rtrim((string) config('app.frontend_url'), '/');
        $path = $this->lang === 'en'
            ? '/en/student/timy/pozvanka'
            : '/student/timy/pozvanka';

        return $base.$path.'?token='.rawurlencode($this->token);
    }

    public function build(): self
    {
        $languageId = $this->lang === 'en' ? 2 : 1;

        $template = EmailTemplate::findBySlug('team_invite')
            ?->forLanguage($languageId);

        $joinUrl = $this->inviteUrl();

        $subject = $template?->subject
            ?? ($this->lang === 'en'
                ? 'Team invitation: '.$this->team->name
                : 'Pozvánka do tímu: '.$this->team->name);

        $bodyHtml = $template
            ? $template->render([
                'inviterName' => $this->inviterDisplayName,
                'teamName' => $this->team->name,
                'joinUrl' => $joinUrl,
                'inviteeEmail' => $this->inviteeEmail,
            ])
            : '';

        if ($bodyHtml === '') {
            $bodyHtml = $this->lang === 'en'
                ? '<p>'.e($this->inviterDisplayName).' invited you to join the team <strong>'.e($this->team->name).'</strong> on NTI.</p>'
                    .'<p>Log in with this email address (<strong>'.e($this->inviteeEmail).'</strong>) and open:</p>'
                    .'<p><a href="'.e($joinUrl).'">'.e($joinUrl).'</a></p>'
                : '<p>'.e($this->inviterDisplayName).' vás pozval/a do tímu <strong>'.e($this->team->name).'</strong> na platforme NTI.</p>'
                    .'<p>Prihláste sa pod týmto e-mailom (<strong>'.e($this->inviteeEmail).'</strong>) a otvorte odkaz:</p>'
                    .'<p><a href="'.e($joinUrl).'">'.e($joinUrl).'</a></p>';
        }

        return $this->subject($subject)
            ->view('notifications::emails.layout')
            ->with([
                'subject' => $subject,
                'body_html' => $bodyHtml,
            ]);
    }
}
