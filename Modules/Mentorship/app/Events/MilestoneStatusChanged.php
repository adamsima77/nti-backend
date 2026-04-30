<?php

namespace Modules\Mentorship\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\IdentityAccess\Models\User;
use Modules\Mentorship\Models\Milestone;

class MilestoneStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Milestone $milestone,
        public ?string $oldStatus,
        public ?string $newStatus,
        public User $changedBy,
    ) {}
}
