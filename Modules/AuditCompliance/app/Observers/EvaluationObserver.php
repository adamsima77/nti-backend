<?php

namespace Modules\AuditCompliance\Observers;

use Modules\AuditCompliance\Models\AuditCompliance;
use Modules\Evaluation\Models\Evaluation;

class EvaluationObserver
{
    /**
     * Handle the Evaluation "created" event.
     */
    public function created($model): void
    {
        $this->log('evaluation.created', $model);
    }

    /**
     * Handle the Evaluation "updated" event.
     */
    public function updated($model): void
    {
        $this->log('evaluation.updated', $model, $model->getDirty());
    }

    /**
     * Handle the Evaluation "deleted" event.
     */
    public function deleted($model): void
    {
        $this->log('evaluation.deleted', $model);
    }

    private function log(string $action, Evaluation $model, array $payload = []): void
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
