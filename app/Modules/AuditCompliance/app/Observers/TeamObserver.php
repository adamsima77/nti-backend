<?php

namespace Modules\AuditCompliance\Observers;

use Modules\AuditCompliance\Models\AuditCompliance;
use Modules\Teams\Models\Team;

class TeamObserver
{
    /**
     * Handle the Team "created" event.
     */
    public function created($model): void
    {
        $this->log('team.created', $model);
    }

    /**
     * Handle the Team "updated" event.
     */
    public function updated($model): void
    {
        $this->log('team.updated', $model, $model->getDirty());
    }

    /**
     * Handle the Team "deleted" event.
     */
    public function deleted($model): void
    {
        $this->log('team.deleted', $model);
    }

    private function log(string $action, Team $model, array $payload = []): void
    {
        if (!request()->user()) {
            return;
        }

        AuditCompliance::log(
            userId: request()->user()->id,
            action: $action,
            objectType: $model::class,
            objectId: $model->id,
            ip: request()->ip(),
            result: 'success',
            resultPayload: $payload ?: null,
        );
    }
}
