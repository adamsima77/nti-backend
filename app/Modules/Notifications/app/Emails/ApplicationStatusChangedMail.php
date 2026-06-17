<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Applications\Models\Application;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Models\EmailTemplate;

class ApplicationStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Application $application,
        public string $newStatus,
        public User $recipient,
        public ?string $note = null,
        public int $languageId = 1,
    ) {}

    public function build(): self
    {
        $template = EmailTemplate::findBySlug('application_status_changed')
            ?->forLanguage($this->languageId);

        $recipientName = trim(($this->recipient->name ?? '').' '.($this->recipient->surname ?? ''));
        $callName = $this->application->call?->name ?? ('Výzva #'.$this->application->call_id);

        $data = [
            'userName'       => $recipientName !== '' ? $recipientName : ($this->recipient->email ?? ''),
            'applicationRef' => $this->application->reference ?? ('APP-'.$this->application->id),
            'callName'       => $callName,
            'newStatus'      => $this->newStatus,
            'note'           => $this->note ?? '',
        ];

        $renderedSubject = $template ? $template->renderSubject($data) : 'Zmena stavu prihlášky';
        $renderedBody    = $template ? $template->render($data) : '';

        return $this->subject($renderedSubject)
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $renderedSubject,
                'body_html' => $renderedBody,
            ]);
    }
}
