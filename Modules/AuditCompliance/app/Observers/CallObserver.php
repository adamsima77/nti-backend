<?php

namespace Modules\AuditCompliance\Observers;

use Modules\AuditCompliance\Models\AuditCompliance;
use Modules\Programs\Models\Call;

class CallObserver
{
    /**
     * Handle the Call "created" event.
     */
    public function created($model): void
    {
        $this->log('call.created', $model);
    }

    /**
     * Handle the Call "updated" event.
     */
    public function updated($model): void
    {
        $this->log('call.updated', $model, $model->getDirty());
    }

    /**
     * Handle the Call "deleted" event.
     */
    public function deleted($model): void
    {
        $this->log('call.deleted', $model);
    }

    private function log(string $action, Call $model, array $payload = []): void
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
