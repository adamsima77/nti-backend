<?php

namespace Modules\AuditCompliance\Observers;

use Modules\AuditCompliance\Models\AuditCompliance;
use Modules\Organizations\Models\Organization;

class OrganizationObserver
{
    /**
     * Handle the OrganizationObserver "created" event.
     */
    public function created($model): void
    {
        $this->log('organization.created', $model);
    }

    /**
     * Handle the OrganizationObserver "updated" event.
     */
    public function updated($model): void
    {
        $this->log('organization.updated', $model, $model->getDirty());
    }

    /**
     * Handle the OrganizationObserver "deleted" event.
     */
    public function deleted($model): void
    {
        $this->log('organization.deleted', $model);
    }

    private function log(string $action, Organization $model, array $payload = []): void
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
