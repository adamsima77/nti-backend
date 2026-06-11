<?php

namespace App\Services;

use Modules\Applications\Events\ApplicationStatusChanged;
use Modules\Applications\Models\Application;
use Modules\Content\Enums\LanguageType;
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
        $languageId = request()->cookie('i18n_redirected', 'sk') === 'en'
            ? LanguageType::ENGLISH->value
            : LanguageType::SLOVAK->value;

        $newStatus = $application->status?->name ?? $status;

        event(new ApplicationStatusChanged($application, $newStatus, $note, $changedBy, $languageId));
    }
}
