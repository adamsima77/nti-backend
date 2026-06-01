<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\ApplicationStatusHistory;
use Modules\Applications\Models\StatusOfApplication;
use Modules\Evaluation\Models\Evaluation;
use Modules\IdentityAccess\Models\User;

class ApplicationWorkflowService
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function changeStatus(
        Application $application,
        ?int $statusId = null,
        ?string $statusName = null,
        ?string $note = null,
        ?User $changedBy = null,
        array $additionalUpdates = []
    ): Application {
        if ($statusId === null && $statusName === null) {
            throw new \InvalidArgumentException('Either statusId or statusName must be provided.');
        }

        $status = $statusId !== null
            ? StatusOfApplication::query()->findOrFail($statusId)
            : StatusOfApplication::query()->firstOrCreate(['name' => $statusName]);

        DB::transaction(function () use ($application, $status, $note, $additionalUpdates) {
            $application->update(array_merge([
                'active_status' => $status->id,
                'last_update' => now(),
            ], $additionalUpdates));

            ApplicationStatusHistory::query()->create([
                'status_of_application_id' => $status->id,
                'application_id' => $application->id,
                'note' => $note,
            ]);
        });

        return $this->loadApplicationDetails($application);
    }

    public function submitApplication(Application $application, ?User $changedBy = null, ?string $note = null): Application
    {
        $application = $this->changeStatus($application, null, 'Podané', $note, $changedBy, ['submitted_at' => now()]);

        $this->notificationService->notifyAdminsApplicationSubmitted($application);

        return $application;
    }

    public function requestSupplement(Application $application, string $reason, ?User $changedBy = null): Application
    {
        $application = $this->changeStatus($application, null, 'Vyžiadané doplnenie', $reason, $changedBy);

        $this->notificationService->notifyTeamApplicationStatusChange($application, 'supplement', $reason, $changedBy);

        return $application;
    }

    public function evaluateApplication(
        Application $application,
        Evaluation $evaluation,
        string $recommendation,
        ?string $note,
        ?User $changedBy = null,
        bool $notifyAdmins = false
    ): Application {
        $statusName = match ($recommendation) {
            'approve' => 'Schválené',
            'reject' => 'Zamietnuté',
            'supplement' => 'Vyžiadané doplnenie',
            default => $recommendation,
        };

        $application = $this->changeStatus($application, null, $statusName, $note, $changedBy);

        if ($notifyAdmins) {
            $this->notificationService->notifyAdminsEvaluationSubmitted($evaluation);
        }

        $this->notificationService->notifyTeamApplicationStatusChange($application, $recommendation, $note, $changedBy);

        return $application;
    }

    public function notifyEvaluatorAssigned(Evaluation $evaluation): void
    {
        $this->notificationService->notifyEvaluatorAssigned($evaluation);
    }

    private function loadApplicationDetails(Application $application): Application
    {
        return $application->load([
            'call:id,name',
            'status:id,name',
            'team:id,name',
            'team.members.student.academicFlags',
            'documents:id',
            'statusHistory.status:id,name',
        ]);
    }
}
