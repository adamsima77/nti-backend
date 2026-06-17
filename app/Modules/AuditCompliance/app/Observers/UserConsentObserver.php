<?php

namespace Modules\AuditCompliance\Observers;

use Modules\AuditCompliance\Models\AuditCompliance;
use Modules\IdentityAccess\Models\UserConsent;

class UserConsentObserver
{
    /**
     * Handle the UserConsent "created" event.
     */
    public function created($model): void
    {
        $this->log('userconsent.created', $model);
    }

    /**
     * Handle the UserConsent "updated" event.
     */
    public function updated($model): void
    {
        $this->log('userconsent.updated', $model, $model->getDirty());
    }

    /**
     * Handle the UserConsent "deleted" event.
     */
    public function deleted($model): void
    {
        $this->log('userconsent.deleted', $model);
    }

    private function log(string $action, UserConsent $model, array $payload = []): void
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
