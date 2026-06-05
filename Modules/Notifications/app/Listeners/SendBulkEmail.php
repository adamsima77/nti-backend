<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\IdentityAccess\Enums\UserStatus;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Emails\BulkEmailMail;
use Modules\Notifications\Events\BulkEmail;
use Modules\Notifications\Models\EmailTemplate;

class SendBulkEmail implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     */
    public function handle(BulkEmail $event): void
    {
        $template = EmailTemplate::find($event->email_id);

        if (!$template) {
            return;
        }


        $query = User::query()
            ->whereNotIn('status_id', [
                UserStatus::ANONYMIZED,
                UserStatus::BANNED,
                UserStatus::INACTIVE,
            ]);

        if ($event->role_id) {
            $query->whereHas('roles', fn($q) => $q->where('roles.id', $event->role_id));
        }

        if ($event->call_id) {
            $callId = $event->call_id;

            $query->where(function ($q) use ($callId) {
                $q->whereHas('teams.applications', fn($a) => $a->where('call_id', $callId)
                )
                    ->orWhereHas('mentorshipsAsMentor', fn($m) => $m->whereHas('application', fn($a) => $a->where('call_id', $callId)
                    )
                    )
                    ->orWhereHas('commissionMemberships.evaluations.application', fn($a) => $a->where('call_id', $callId)
                    );
            });
        }

        $users = $query->get();

        if ($users->isEmpty()) {

            return;
        }



        foreach ($users as $user) {
            try {
                $data = [
                    //Temporary fix may need other solution
                    'userName' => $user->name,
                    'name' => $user->name,
                    'userEmail' => $user->email,


                    'organizationName' => '',
                    'teamName' => '',
                    'milestoneName' => '',
                ];

                $subject = $event->subject ?: $template->renderSubject($data);
                $body = $template->render($data);

                Mail::to($user->email)->send(new BulkEmailMail($subject, $body));
            } catch (\Throwable $e) {

            }
        }
    }
}
