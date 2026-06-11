<?php

namespace Modules\Programs\StateMachines;

use Modules\Programs\Events\CallClosed;
use Modules\Programs\Models\Call;
use Modules\Programs\Models\StatusOfCall;
use Modules\Programs\Models\StatusOfCallHasCall;

class CallStateMachineProgramA
{
    public const STATE_DRAFT       = 'Draft';
    public const STATE_PUBLISHED   = 'Publikované';
    public const STATE_IN_PROGRESS = 'V realizácii';
    public const STATE_CLOSED      = 'Uzavreté';

    private const TRANSITIONS = [
        self::STATE_DRAFT       => [self::STATE_PUBLISHED],
        self::STATE_PUBLISHED => [self::STATE_IN_PROGRESS],
        self::STATE_IN_PROGRESS => [self::STATE_CLOSED],
        self::STATE_CLOSED      => [],
    ];

    private const REQUIRED_FIELDS = [
        self::STATE_PUBLISHED => [
            'name',
            'description',
            'application_deadline',
            'project_start',
            'project_end',
            'call_type_id',
        ],
        self::STATE_IN_PROGRESS => [],
        self::STATE_CLOSED      => [],
    ];

    public function __construct(private Call $call) {}

    public function currentState(): string
    {
        return $this->call
            ->statusHistory()
            ->with('status')
            ->latest('id')
            ->first()
            ?->status
            ?->name ?? self::STATE_DRAFT;
    }

    public function canTransitionTo(string $targetState): bool
    {
        $current = $this->currentState();

        return in_array($targetState, self::TRANSITIONS[$current] ?? []);
    }

    public function missingFields(string $targetState): array
    {
        $required = self::REQUIRED_FIELDS[$targetState] ?? [];

        if($this->call->program_id == 1){
            $required = array_diff($required, ['organization_id']);
        }

        return collect($required)
            ->filter(fn($field) => empty($this->call->$field))
            ->values()
            ->toArray();
    }

    public function transitionTo(string $targetState, ?string $note = null): StatusOfCallHasCall
    {
        if (!$this->canTransitionTo($targetState)) {
            throw new \InvalidArgumentException(
                "Prechod zo stavu '{$this->currentState()}' do stavu '{$targetState}' nie je povolený."
            );
        }

        $missing = $this->missingFields($targetState);

        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Chýbajú povinné polia pre prechod do stavu "' . $targetState . '": ' . implode(', ', $missing)
            );
        }

        $status = StatusOfCall::where('name', $targetState)->firstOrFail();

        $record = StatusOfCallHasCall::create([
            'call_id'           => $this->call->id,
            'status_of_call_id' => $status->id,
            'note'              => $note,
        ]);

        if ($targetState === self::STATE_CLOSED) {
            CallClosed::dispatch($this->call);
        }

        return $record;
    }

    public function availableTransitions(): array
    {
        return self::TRANSITIONS[$this->currentState()] ?? [];
    }
}
