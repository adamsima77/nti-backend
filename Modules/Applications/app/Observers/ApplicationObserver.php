<?php

namespace Modules\Applications\Observers;

use Modules\Applications\Models\Application;

class ApplicationObserver
{
    /**
     * Handle the Application "created" event.
     */
    public function created(Application $application): void {
        $application->reference = 'APP-' . str_pad($application->id, 3, '0', STR_PAD_LEFT);
        $application->saveQuietly();
    }

    /**
     * Handle the Application "updated" event.
     */
    public function updated(Application $application): void {}

    /**
     * Handle the Application "deleted" event.
     */
    public function deleted(Application $application): void {}

    /**
     * Handle the Application "restored" event.
     */
    public function restored(Application $application): void {}

    /**
     * Handle the Application "force deleted" event.
     */
    public function forceDeleted(Application $application): void {}
}
