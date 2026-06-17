<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Modules\Applications\Models\Application;
use Modules\Content\Enums\LanguageType;
use Modules\Mentorship\Events\MilestoneStatusChanged;
use Modules\Mentorship\Models\Milestone;
use Modules\Notifications\Emails\MilestoneStatusChangedMail;
use Modules\Notifications\Services\MilestoneNotificationService;

class SendMilestoneStatusChangedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(MilestoneStatusChanged $event): void
    {
        $milestone = $event->milestone;


        $applications = Application::query()
            ->where('call_id', $milestone->call_id)
            ->with(['creator', 'team.members'])
            ->get();

        if ($applications->isEmpty()) {
            return;
        }


        $recipients = collect();

        foreach ($applications as $application) {
            if ($application->creator) {
                $recipients->push($application->creator);
            }
            if ($application->team?->members) {
                $recipients = $recipients->merge($application->team->members);
            }
        }


        $recipients = $recipients->where('id', '!==', $event->changedBy->id)->unique('id');


        foreach ($recipients as $user) {
            if (!empty($user->email)) {
                Mail::to($user->email)->send(new MilestoneStatusChangedMail(
                    $milestone,
                    $event->oldStatus,
                    $event->newStatus,
                    $user,
                    $event->changedBy,
                    $event->languageId ?: (int) LanguageType::SLOVAK->value
                ));
            }
        }
    }
}
