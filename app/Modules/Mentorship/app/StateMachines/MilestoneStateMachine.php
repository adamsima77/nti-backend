<?php

namespace Modules\Mentorship\StateMachines;

use Modules\Mentorship\Models\Milestone;
use Modules\Mentorship\Models\MilestoneStatus;

class MilestoneStateMachine
{
    public const STATE_PLANNED   = 'Plánované';
    public const STATE_IN_PROGRESS = 'V riešení';
    public const STATE_DONE      = 'Dokončené';
    public const STATE_APPROVED  = 'Schválené';
    public const STATE_REJECTED  = 'Zamietnuté';

    private const TRANSITIONS = [
        self::STATE_PLANNED     => [self::STATE_IN_PROGRESS],
        self::STATE_IN_PROGRESS => [self::STATE_DONE],
        self::STATE_DONE        => [self::STATE_APPROVED, self::STATE_REJECTED],
        self::STATE_REJECTED    => [self::STATE_IN_PROGRESS],
        self::STATE_APPROVED    => [],
    ];

    public function __construct(private Milestone $milestone) {}

    public function currentState(): string
    {
        $this->milestone->loadMissing('milestoneStatus');

        return $this->milestone->milestoneStatus?->name ?? self::STATE_PLANNED;
    }

    public function canTransitionTo(string $targetState): bool
    {
        return in_array($targetState, self::TRANSITIONS[$this->currentState()] ?? [], true);
    }

    public function transitionTo(string $targetState): Milestone
    {
        if (! $this->canTransitionTo($targetState)) {
            throw new \InvalidArgumentException(
                "Prechod zo stavu '{$this->currentState()}' do stavu '{$targetState}' nie je povolený."
            );
        }

        $status = MilestoneStatus::where('name', $targetState)->firstOrFail();

        $this->milestone->update(['milestone_status_id' => $status->id]);
        $this->milestone->unsetRelation('milestoneStatus');

        return $this->milestone;
    }

    public function availableTransitions(): array
    {
        return self::TRANSITIONS[$this->currentState()] ?? [];
    }
}
