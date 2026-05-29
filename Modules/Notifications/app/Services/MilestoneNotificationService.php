<?php

namespace Modules\Notifications\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Modules\Content\Enums\LanguageType;
use Modules\IdentityAccess\Models\User;
use Modules\Mentorship\Models\Milestone;
use Modules\Notifications\Emails\MilestoneStatusChangedMail;
use Modules\Notifications\Models\NotificationCategory;
use Modules\Notifications\Models\Notifications;

class MilestoneNotificationService
{
    public function notifyTeam(
        Milestone $milestone,
        ?string $oldStatus,
        string $newStatus,
        User $changedBy,
        int $languageId = 1,
    ): void {
        $milestone->loadMissing([
            'application.team.members',
            'application.creator',
            'application.call',
        ]);

        $application = $milestone->application;

        if ($application === null) {
            return;
        }

        $recipients = $this->teamRecipients($application->creator, $application->team?->members, $changedBy);

        if ($recipients->isEmpty()) {
            return;
        }

        $categoryId = NotificationCategory::query()->where('slug', 'milestone')->value('id');

        if ($categoryId === null) {
            return;
        }

        [$title, $body] = $this->portalMessage($milestone, $newStatus, $changedBy, $application);

        foreach ($recipients as $recipient) {
            Notifications::query()->create([
                'user_id' => $recipient->id,
                'notification_category_id' => $categoryId,
                'notifiable_type' => Milestone::class,
                'notifiable_id' => $milestone->id,
                'title' => $title,
                'body' => $body,
                'is_read' => false,
            ]);

            if (filled($recipient->email)) {
                Mail::to($recipient->email)->send(new MilestoneStatusChangedMail(
                    $milestone,
                    $oldStatus,
                    $newStatus,
                    $recipient,
                    $changedBy,
                    $languageId ?: LanguageType::SLOVAK->value,
                ));
            }
        }
    }

    /**
     * @param  Collection<int, User>|null  $members
     * @return Collection<int, User>
     */
    private function teamRecipients(?User $creator, ?Collection $members, User $exclude): Collection
    {
        $recipients = collect();

        if ($creator !== null && $creator->id !== $exclude->id) {
            $recipients->push($creator);
        }

        foreach ($members ?? [] as $member) {
            if ($member->id !== $exclude->id) {
                $recipients->push($member);
            }
        }

        return $recipients->unique('id')->values();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function portalMessage(Milestone $milestone, string $newStatus, User $changedBy, $application): array
    {
        $projectName = $application->call?->name ?? ('Projekt #'.$application->id);
        $mentorName = trim(($changedBy->name ?? '').' '.($changedBy->surname ?? '')) ?: ($changedBy->email ?? 'Mentor');
        $milestoneName = $milestone->name;

        if ($this->isApprovedStatus($newStatus)) {
            return [
                'Míľnik bol schválený',
                sprintf(
                    'Mentor %s schválil míľnik „%s“ v projekte %s.',
                    $mentorName,
                    $milestoneName,
                    $projectName,
                ),
            ];
        }

        if ($this->isRejectedStatus($newStatus)) {
            return [
                'Míľnik bol vrátený na doplnenie',
                sprintf(
                    'Mentor %s vrátil míľnik „%s“ v projekte %s. Skontrolujte komentár mentora a doplňte výstupy.',
                    $mentorName,
                    $milestoneName,
                    $projectName,
                ),
            ];
        }

        return [
            'Zmena stavu míľnika',
            sprintf('Míľnik „%s“ v projekte %s bol aktualizovaný.', $milestoneName, $projectName),
        ];
    }

    private function isApprovedStatus(string $status): bool
    {
        $value = mb_strtolower($status);

        return in_array($value, ['completed', 'approved'], true)
            || str_contains($value, 'schválen')
            || str_contains($value, 'schvalen');
    }

    private function isRejectedStatus(string $status): bool
    {
        $value = mb_strtolower($status);

        return str_contains($value, 'reject') || str_contains($value, 'zamiet');
    }
}
