<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Modules\Content\Enums\LanguageType;
use Modules\IdentityAccess\Events\UserBanned;
use Modules\Notifications\Emails\SendUserBannedMail;

class SendBannedEmail implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(UserBanned $event): void
    {
        $user = $event->user;
        $languageId = LanguageType::ENGLISH->value;

        Mail::to($user->email)->send(
            new SendUserBannedMail($user, $languageId)
        );
    }
}
