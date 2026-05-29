<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Mentorship\Events\MilestoneStatusChanged;
use Modules\Notifications\Services\MilestoneNotificationService;

class SendMilestoneStatusChangedNotification implements ShouldQueue
{
    public function handle(MilestoneStatusChanged $event): void
    {
        app(MilestoneNotificationService::class)->notifyTeam(
            $event->milestone,
            $event->oldStatus,
            $event->newStatus,
            $event->changedBy,
            $event->languageId,
        );
    }
}
