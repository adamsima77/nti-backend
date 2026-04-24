<?php

namespace Modules\Notifications\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Modules\IdentityAccess\Events\PasswordResetRequested;
use Modules\Notifications\Emails\ResetPasswordMail;

class SendPasswordResetEmail
{
    public function handle(PasswordResetRequested $event): void
    {
        $user = $event->user;

        $token = Password::createToken($user);

        Mail::to($user->email)
            ->send(new ResetPasswordMail($token, $user));
    }
}
