<?php

namespace Modules\Applications\StateMachines;

use Illuminate\Support\Facades\DB;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\ApplicationStatusHistory;
use Modules\Applications\Models\StatusOfApplication;
use Modules\Evaluation\Models\Evaluation;
use Modules\IdentityAccess\Models\User;

class ApplicationStateMachine
{
    public const STATE_DRAFT                = 'Draft';
    public const STATE_SUBMITTED            = 'Podané';
    public const STATE_IN_EVALUATION        = 'V hodnotení';
    public const STATE_SUPPLEMENT_REQUESTED = 'Vyžiadané doplnenie';
    public const STATE_APPROVED             = 'Schválené';
    public const STATE_REJECTED             = 'Zamietnuté';
    public const STATE_PAUSED               = 'Pozastavené';
    public const STATE_ONBOARDING           = 'Onboarding';
    public const STATE_ACTIVE_PROJECT       = 'Aktívny projekt';
    public const STATE_COMPLETED            = 'Ukončené';

    private const TRANSITIONS = [
        self::STATE_DRAFT => [self::STATE_SUBMITTED],
        self::STATE_SUBMITTED => [self::STATE_IN_EVALUATION, self::STATE_SUPPLEMENT_REQUESTED],
        self::STATE_SUPPLEMENT_REQUESTED => [self::STATE_SUBMITTED],
        self::STATE_IN_EVALUATION => [self::STATE_APPROVED, self::STATE_REJECTED, self::STATE_SUPPLEMENT_REQUESTED],
        self::STATE_APPROVED => [self::STATE_ONBOARDING],
        self::STATE_ONBOARDING => [self::STATE_ACTIVE_PROJECT],
        self::STATE_ACTIVE_PROJECT => [self::STATE_PAUSED, self::STATE_COMPLETED],
        self::STATE_PAUSED => [self::STATE_ACTIVE_PROJECT, self::STATE_COMPLETED],
        self::STATE_REJECTED  => [],
        self::STATE_COMPLETED => [],
    ];

    private const REQUIRED_FIELDS = [
        self::STATE_SUBMITTED => ['team_id', 'call_id'],
        self::STATE_IN_EVALUATION => ['commission_id'],
        self::STATE_APPROVED      => [],
        self::STATE_ONBOARDING    => [],
        self::STATE_ACTIVE_PROJECT => ['mentor_id']
    ];

    public function __construct(
        private Application $application,
        private ?User $actor = null
    ) {}

    /**
     * Získa aktuálny stav aplikácie na základe relácie.
     */
    public function currentState(): string
    {
        // POZOR: V modeli Application musíte mať reláciu definovanú takto:
        // public function status() { return $this->belongsTo(StatusOfApplication::class, 'active_status'); }
        $this->application->loadMissing('status');

        return $this->application->status?->name ?? self::STATE_DRAFT;
    }

    public function canTransitionTo(string $targetState): bool
    {
        return in_array($targetState, self::TRANSITIONS[$this->currentState()] ?? [], true);
    }

    public function missingFields(string $targetState): array
    {
        $required = self::REQUIRED_FIELDS[$targetState] ?? [];

        return collect($required)
            ->filter(function ($field) {

                if ($field === 'mentor_id') {
                    $this->application->loadMissing('mentorships');
                    return $this->application->mentorships->isEmpty();
                }

                if ($field === 'commission_id') {
                    return !Evaluation::where('application_id', $this->application->id)->exists();
                }

                return empty($this->application->$field);
            })
            ->values()
            ->toArray();
    }

    /**
     * Bezpečne vykoná zmenu stavu.
     */
    public function transitionTo(string $targetState, ?string $note = null): Application
    {
        if (!$this->canTransitionTo($targetState)) {
            throw new \InvalidArgumentException(
                "Prechod zo stavu '{$this->currentState()}' do stavu '{$targetState}' nie je povolený."
            );
        }

        $missing = $this->missingFields($targetState);
        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Chýbajú povinné dáta pre prechod do stavu "' . $targetState . '": ' . implode(', ', $missing)
            );
        }

        $status = StatusOfApplication::where('name', $targetState)->firstOrFail();

        DB::transaction(function () use ($status, $targetState, $note) {
            $updates = [
                'active_status' => $status->id,
                'last_update'   => now(),
            ];

            if ($targetState === self::STATE_SUBMITTED) {
                $updates['submitted_at'] = now();
            }

            $this->application->update($updates);

            ApplicationStatusHistory::query()->create([
                'status_of_application_id' => $status->id,
                'application_id'           => $this->application->id,
                'note'                     => $note,
                'changed_by'               => $this->actor?->id,
            ]);
        });

        $this->application->unsetRelation('status');

        return $this->application;
    }

    public function availableTransitions(): array
    {
        return self::TRANSITIONS[$this->currentState()] ?? [];
    }
}
