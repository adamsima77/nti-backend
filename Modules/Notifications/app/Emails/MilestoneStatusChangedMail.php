<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\IdentityAccess\Models\User;
use Modules\Mentorship\Models\Milestone;
use Modules\Notifications\Models\EmailTemplate;

class MilestoneStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Milestone $milestone,
        public ?string $oldStatus,
        public ?string $newStatus,
        public User $recipient,
        public User $changedBy,
        public int $languageId
    ) {}

    public function build(): self
    {
        $template = EmailTemplate::findBySlug('milestone_status_changed')
            ?->forLanguage($this->languageId);

        $recipientName = trim(($this->recipient->name ?? '').' '.($this->recipient->surname ?? ''));
        $actorName = trim(($this->changedBy->name ?? '').' '.($this->changedBy->surname ?? ''));


        $oldStatusText = $this->oldStatus ?: '-';
        $newStatusText = $this->newStatus ?: '-';

        $data = [
            'userName'      => $recipientName !== '' ? $recipientName : ($this->recipient->email ?? ''),
            'milestoneName' => $this->milestone->name,


            'oldStatus'     => $oldStatusText,
            'newStatus'     => $newStatusText,


            'oldStatus ?? \'-\'' => $oldStatusText,
            'newStatus ?? \'-\'' => $newStatusText,

            'deadline'      => optional($this->milestone->deadline)->format('d.m.Y'),
            'actorName'     => $actorName !== '' ? $actorName : ($this->changedBy->email ?? 'Mentor'),
            'projectId'     => $this->milestone->application?->id,
        ];

        $renderedSubject = $template ? $template->renderSubject($data) : 'Zmena stavu míľnika';
        $renderedBody    = $template ? $template->render($data) : '';

        return $this->subject($renderedSubject)
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $renderedSubject,
                'body_html' => $renderedBody,
            ]);
    }
}
