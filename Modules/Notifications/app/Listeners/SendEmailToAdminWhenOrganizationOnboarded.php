<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Modules\IdentityAccess\Models\Role;
use Modules\Notifications\Emails\NotifyAdminOrgOnboard;

class SendEmailToAdminWhenOrganizationOnboarded implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle($event): void {
        $organization = $event->organization->load('sectors', 'address');

        $admins = Role::whereIn('name', ['nti_admin', 'nti_superadmin'])
            ->with('users')
            ->get()
            ->flatMap(fn($role) => $role->users)
            ->unique('id');

        foreach ($admins as $admin) {
            Mail::to($admin->email)->queue(
                new NotifyAdminOrgOnboard($organization, $admin->email)
            );
        }
    }
}
