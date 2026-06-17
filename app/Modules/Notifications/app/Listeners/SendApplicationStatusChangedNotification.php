<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Modules\Applications\Events\ApplicationStatusChanged;
use Modules\Applications\Models\Application;
use Modules\Content\Enums\LanguageType;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Emails\ApplicationStatusChangedMail;
use Modules\Notifications\Models\NotificationCategory;
use Modules\Notifications\Models\Notifications;

class SendApplicationStatusChangedNotification implements ShouldQueue
{
    public function handle(ApplicationStatusChanged $event): void
    {
        $application = $event->application;
        $application->loadMissing(['team.members', 'creator', 'status']);

        $recipients = collect();

        if ($application->creator) {
            $recipients->push($application->creator);
        }

        foreach ($application->team?->members ?? [] as $member) {
            $recipients->push($member);
        }

        if ($event->changedBy) {
            $recipients = $recipients->filter(fn (User $u) => $u->id !== $event->changedBy->id);
        }

        $recipients = $recipients->unique('id')->values();

        if ($recipients->isEmpty()) {
            return;
        }

        $categoryId = NotificationCategory::query()->where('slug', 'status_change')->value('id')
            ?? NotificationCategory::query()->where('slug', 'application')->value('id');

        $lang        = $event->languageId === LanguageType::ENGLISH->value;
        $statusLower = mb_strtolower($event->newStatus);

        if (str_contains($statusLower, 'supplement') || str_contains($statusLower, 'dopln')) {
            $title = $lang ? 'Application returned for supplementation' : 'Prihláška vrátená na doplnenie';
            $body  = $lang
                ? sprintf('Application #%d was returned for supplementation. %s', $application->id, $event->note ?? '')
                : sprintf('Prihláška č. %d bola vrátená na doplnenie. %s', $application->id, $event->note ?? '');
        } elseif (str_contains($statusLower, 'approve') || str_contains($statusLower, 'schv')) {
            $title = $lang ? 'Application approved' : 'Prihláška schválená';
            $body  = $lang
                ? sprintf('Application #%d has been approved.', $application->id)
                : sprintf('Prihláška č. %d bola schválená.', $application->id);
        } elseif (str_contains($statusLower, 'reject') || str_contains($statusLower, 'zamiet')) {
            $title = $lang ? 'Application rejected' : 'Prihláška zamietnutá';
            $body  = $lang
                ? sprintf('Application #%d has been rejected. %s', $application->id, $event->note ?? '')
                : sprintf('Prihláška č. %d bola zamietnutá. %s', $application->id, $event->note ?? '');
        } else {
            $title = $lang ? 'Application status changed' : 'Zmena stavu prihlášky';
            $body  = $lang
                ? sprintf('The status of application #%d has changed to: %s.', $application->id, $event->newStatus)
                : sprintf('Stav prihlášky č. %d sa zmenil na: %s.', $application->id, $event->newStatus);
        }

        foreach ($recipients as $recipient) {
            if ($categoryId !== null) {
                Notifications::query()->create([
                    'user_id'                  => $recipient->id,
                    'notification_category_id' => $categoryId,
                    'notifiable_type'          => Application::class,
                    'notifiable_id'            => $application->id,
                    'title'                    => $title,
                    'body'                     => $body,
                    'is_read'                  => false,
                ]);
            }

            if (filled($recipient->email)) {
                Mail::to($recipient->email)->send(new ApplicationStatusChangedMail(
                    $application,
                    $event->newStatus,
                    $recipient,
                    $event->note,
                    $event->languageId,
                ));
            }
        }
    }
}
