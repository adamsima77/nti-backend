<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Modules\Applications\Models\Application;
use Modules\Evaluation\Models\Evaluation;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Models\NotificationCategory;
use Modules\Notifications\Models\Notifications;

class NotificationService
{
    public function notifyAdminsApplicationSubmitted(Application $application): void
    {
        $categoryId = NotificationCategory::query()->where('slug', 'application')->value('id');
        if ($categoryId === null) {
            return;
        }

        $admins = User::query()
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['nti_admin', 'nti_superadmin']);
            })->get();

        if ($admins->isEmpty()) {
            return;
        }

        $title = 'Nová prihláška';
        $body = sprintf('Prihláška č. %d bola podaná.', $application->id);

        foreach ($admins as $admin) {
            Notifications::query()->create([
                'user_id' => $admin->id,
                'notification_category_id' => $categoryId,
                'notifiable_type' => Application::class,
                'notifiable_id' => $application->id,
                'title' => $title,
                'body' => $body,
                'is_read' => false,
            ]);
        }
    }

    public function notifyAdminsEvaluationSubmitted(Evaluation $evaluation): void
    {
        $categoryId = NotificationCategory::query()->where('slug', 'evaluation')->value('id');
        if ($categoryId === null) {
            return;
        }

        $admins = User::query()
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['nti_admin', 'nti_superadmin']);
            })->get();

        if ($admins->isEmpty()) {
            return;
        }

        $title = 'Nové hodnotenie prihlášky';
        $body = sprintf('Hodnotenie pre prihlášku č. %d bolo odoslané.', $evaluation->application_id);

        foreach ($admins as $admin) {
            Notifications::query()->create([
                'user_id' => $admin->id,
                'notification_category_id' => $categoryId,
                'notifiable_type' => Evaluation::class,
                'notifiable_id' => $evaluation->id,
                'title' => $title,
                'body' => $body,
                'is_read' => false,
            ]);
        }
    }

    public function notifyEvaluatorAssigned(Evaluation $evaluation): void
    {
        $commissionMember = $evaluation->commissionMember;
        $recipient = $commissionMember?->user;

        if ($recipient === null) {
            return;
        }

        $categoryId = NotificationCategory::query()->where('slug', 'evaluation')->value('id');
        if ($categoryId === null) {
            return;
        }

        $title = 'Boli ste priradený ako hodnotiteľ';
        $body = sprintf('Ste priradený hodnotiť prihlášku č. %d.', $evaluation->application_id);

        Notifications::query()->create([
            'user_id' => $recipient->id,
            'notification_category_id' => $categoryId,
            'notifiable_type' => Evaluation::class,
            'notifiable_id' => $evaluation->id,
            'title' => $title,
            'body' => $body,
            'is_read' => false,
        ]);
    }

    /**
     * Notify application team (creator + team members) about status changes or supplement requests
     * $status may be a recommendation key like 'approve'|'reject'|'supplement' or a human-readable status.
     */
    public function notifyTeamApplicationStatusChange(Application $application, string $status, ?string $note, ?User $changedBy = null): void
    {
        $application->loadMissing(['team.members', 'creator']);

        $recipients = collect();
        if ($application->creator) {
            $recipients->push($application->creator);
        }

        foreach ($application->team?->members ?? [] as $member) {
            $recipients->push($member);
        }

        if ($changedBy) {
            $recipients = $recipients->filter(fn($u) => $u->id !== $changedBy->id);
        }

        $recipients = $recipients->unique('id')->values();
        if ($recipients->isEmpty()) {
            return;
        }

        $categoryId = NotificationCategory::query()->where('slug', 'status_change')->value('id')
            ?? NotificationCategory::query()->where('slug', 'application')->value('id');

        if ($categoryId === null) {
            return;
        }

        $statusLower = mb_strtolower($status);

        if (str_contains($statusLower, 'supplement') || str_contains($statusLower, 'dopln')) {
            $title = 'Prihláška vrátená na doplnenie';
            $body = sprintf('Prihláška č. %d bola vrátená na doplnenie. %s', $application->id, $note ?? '');
        } elseif (str_contains($statusLower, 'approve') || str_contains($statusLower, 'schv')) {
            $title = 'Prihláška schválená';
            $body = sprintf('Prihláška č. %d bola schválená.', $application->id);
        } elseif (str_contains($statusLower, 'reject') || str_contains($statusLower, 'zamiet')) {
            $title = 'Prihláška zamietnutá';
            $body = sprintf('Prihláška č. %d bola zamietnutá. %s', $application->id, $note ?? '');
        } else {
            $title = 'Zmena stavu prihlášky';
            $body = sprintf('Stav prihlášky č. %d sa zmenil. %s', $application->id, $note ?? '');
        }

        foreach ($recipients as $recipient) {
            Notifications::query()->create([
                'user_id' => $recipient->id,
                'notification_category_id' => $categoryId,
                'notifiable_type' => Application::class,
                'notifiable_id' => $application->id,
                'title' => $title,
                'body' => $body,
                'is_read' => false,
            ]);
        }
    }
}
