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
        public User $user,
    ) {}

    public function build(): self
    {
        $languageId = request()->cookie('i18n_redirected', 'sk') === 'en' ? 2 : 1;

        $template = EmailTemplate::findBySlug('milestone_status_changed')
            ?->forLanguage($languageId);;

        return $this->subject($template?->subject ?? 'Zmena stavu míľnika')
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $template?->subject ?? '',
                'body_html' => $template?->render([
                        'userName'      => $this->user->name . ' ' . $this->user->surname,
                        'milestoneName' => $this->milestone->name,
                        'oldStatus'     => $this->oldStatus,
                        'newStatus'     => $this->newStatus,
                        'deadline'      => optional($this->milestone->deadline)->format('d.m.Y'),
                        'actorName'     => $this->user->name . ' ' . $this->user->surname,
                        'projectId'     => $this->milestone->application?->id,
                    ]) ?? '',
            ]);
    }
}
