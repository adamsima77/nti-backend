<?php

namespace Modules\AuditCompliance\Observers;

use Modules\AuditCompliance\Models\AuditCompliance;
use Modules\Students\Models\Student;

class StudentObserver
{
    /**
     * Handle the Students "created" event.
     */
    public function created(Student $model): void
    {
        $this->log('student.created', $model);
    }

    /**
     * Handle the Students "updated" event.
     */
    public function updated(Student $model): void
    {
        $this->log('student.updated', $model, $model->getDirty());
    }

    /**
     * Handle the Students "deleted" event.
     */
    public function deleted(Student $model): void
    {
        $this->log('student.deleted', $model);
    }

    private function log(string $action, Student $model, array $payload = []): void
    {
        if (!request()->user()) {
            return;
        }

        AuditCompliance::log(
            userId: request()->user()->id,
            action: $action,
            objectType: Student::class,
            objectId: $model->id,
            ip: request()->ip(),
            result: 'success',
            resultPayload: $payload ?: null,
        );
    }
}
