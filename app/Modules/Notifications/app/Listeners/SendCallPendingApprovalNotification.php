<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Modules\Content\Enums\LanguageType;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Emails\CallPendingApprovalMail;
use Modules\Notifications\Models\NotificationCategory;
use Modules\Notifications\Models\Notifications;
use Modules\Programs\Events\CallPendingApproval;

class SendCallPendingApprovalNotification implements ShouldQueue
{
    public function handle(CallPendingApproval $event): void
    {
        $call = $event->call;

        $admins = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['nti_admin', 'nti_superadmin']))
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        $categoryId = NotificationCategory::query()->where('slug', 'call')->value('id')
            ?? NotificationCategory::query()->where('slug', 'application')->value('id');

        $languageId = request()->cookie('i18n_redirected', 'sk') === 'en'
            ? LanguageType::ENGLISH->value
            : LanguageType::SLOVAK->value;

        foreach ($admins as $admin) {
            if ($categoryId !== null) {
                Notifications::query()->create([
                    'user_id'                  => $admin->id,
                    'notification_category_id' => $categoryId,
                    'notifiable_type'          => \Modules\Programs\Models\Call::class,
                    'notifiable_id'            => $call->id,
                    'title'                    => 'Výzva čaká na schválenie',
                    'body'                     => sprintf('Výzva „%s" bola odoslaná na schválenie.', $call->name ?? ('Výzva #'.$call->id)),
                    'is_read'                  => false,
                ]);
            }

            if (filled($admin->email)) {
                Mail::to($admin->email)->send(new CallPendingApprovalMail(
                    $call,
                    $admin,
                    $languageId,
                ));
            }
        }
    }
}
