<?php

namespace Modules\AuditCompliance\Observers;

use Modules\Applications\Models\Application;
use Modules\AuditCompliance\Models\AuditCompliance;

class ApplicationObserver
{
    /**
     * Handle the Application "created" event.
     */
    public function created(Application $model): void
    {
        $this->log('application.created', $model);
    }

    /**
     * Handle the Application "updated" event.
     */
    public function updated(Application $model): void
    {
        $this->log('application.updated', $model, $model->getDirty());
    }

    /**
     * Handle the Application "deleted" event.
     */
    public function deleted(Application $model): void
    {
        $this->log('application.deleted', $model);
    }

    private function log(string $action, Application $model, array $payload = []): void
    {
        $actor = request()->user();

        if (!$actor) {
            return;
        }

        AuditCompliance::log(
            userId: $actor->id,
            action: $action,
            objectType: Application::class,
            objectId: $model->id,
            ip: request()->ip(),
            result: 'success',
            resultPayload: $payload ?: null,
        );
    }
}
