<?php

namespace Modules\Applications\Observers;

use Modules\Applications\Models\Application;
use Modules\Applications\Models\StatusOfApplication;
use Modules\Programs\Models\Call;
use Modules\Programs\StateMachines\CallStateMachine;

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
    public function updated(Application $application): void
    {
        if (!$application->wasChanged('active_status')) {
            return;
        }

        $submittedStatus = StatusOfApplication::where('name', 'Podané')->first();
        if (!$submittedStatus || $application->active_status != $submittedStatus->id) {
            return;
        }

        if (!$application->call_id) {
            return;
        }

        $call = Call::find($application->call_id);
        if (!$call) {
            return;
        }

        $sm = new CallStateMachine($call);

        if ($sm->canTransitionTo(CallStateMachine::STATE_MATCHING)) {
            $sm->transitionTo(CallStateMachine::STATE_MATCHING, 'Automaticky – prvá podaná prihláška');
        }
    }

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
