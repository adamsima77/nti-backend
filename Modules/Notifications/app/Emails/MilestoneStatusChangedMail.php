<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\IdentityAccess\Models\User;
use Modules\Mentorship\Models\Milestone;

class MilestoneStatusChangedMail extends Mailable implements ShouldQueue
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
        return $this->subject('Zmena stavu míľnika')
            ->view('notifications::emails.milestone-status-changed')
            ->with([
                'milestone' => $this->milestone,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
                'user' => $this->user,
            ]);
    }
}
