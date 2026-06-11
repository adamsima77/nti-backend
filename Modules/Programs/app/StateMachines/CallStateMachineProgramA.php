<?php

namespace Modules\Programs\StateMachines;

use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Models\NotificationCategory;
use Modules\Notifications\Models\Notifications;
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

        $this->notifyCallStatusChange($targetState);

        return $record;
    }

    public function availableTransitions(): array
    {
        return self::TRANSITIONS[$this->currentState()] ?? [];
    }

    private function notifyCallStatusChange(string $newStatus): void
    {
        $categoryId = NotificationCategory::query()->where('slug', 'status_change')->value('id');
        if ($categoryId === null) {
            return;
        }

        $this->call->loadMissing('organization.users');

        $lang     = request()->cookie('i18n_redirected', 'sk');
        $callName = $this->call->name ?? ('Call #'.$this->call->id);
        $title    = $lang === 'en' ? 'Call status changed' : 'Zmena stavu výzvy';
        $body     = $lang === 'en'
            ? 'The status of call "'.$callName.'" has changed to: '.$newStatus.'.'
            : 'Stav výzvy „'.$callName.'" sa zmenil na: '.$newStatus.'.';

        $recipients = collect();

        foreach ($this->call->organization?->users ?? [] as $user) {
            if ($user->pivot->organization_role === 'org_admin') {
                $recipients->push($user);
            }
        }

        if ($this->call->po_user_id !== null) {
            $po = User::find($this->call->po_user_id);
            if ($po !== null) {
                $recipients->push($po);
            }
        }

        $recipients->unique('id')->each(function (User $user) use ($categoryId, $title, $body) {
            Notifications::query()->create([
                'user_id'                  => $user->id,
                'notification_category_id' => $categoryId,
                'notifiable_type'          => Call::class,
                'notifiable_id'            => $this->call->id,
                'title'                    => $title,
                'body'                     => $body,
                'is_read'                  => false,
            ]);
        });
    }
}
