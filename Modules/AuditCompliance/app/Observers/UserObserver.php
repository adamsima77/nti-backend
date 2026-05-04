<?php

namespace Modules\AuditCompliance\Observers;

use Modules\AuditCompliance\Models\AuditCompliance;
use Modules\IdentityAccess\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $model): void
    {
        $this->log('user.created', $model);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $model): void
    {
        $this->log('user.updated', $model, $model->getDirty());
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $model): void
    {
        $this->log('user.deleted', $model);
    }

    private function log(string $action, User $model, array $payload = []): void
    {
        $actor = request()->user();

        if (!$actor) {
            return;
        }

        AuditCompliance::log(
            userId: $actor->id,
            action: $action,
            objectType: User::class,
            objectId: $model->id,
            ip: request()->ip(),
            result: 'success',
            resultPayload: $payload ?: null,
        );
    }
}
