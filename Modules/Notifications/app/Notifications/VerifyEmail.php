<?php

namespace Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $signedUrl = URL::temporarySignedRoute(
            'api.verification.verify',
            now()->addMinutes(15),
            [
                'id' => $notifiable->id,
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        parse_str(parse_url($signedUrl, PHP_URL_QUERY), $params);

        $frontendUrl = config('app.frontend_url') . '/auth/verify-email?' . http_build_query([
                'id'        => $notifiable->id,
                'hash'      => $params['hash'],
                'expires'   => $params['expires'],
                'signature' => $params['signature'],
            ]);

        return (new MailMessage)
            ->subject('Verify your email address')
            ->view('notifications::emails.verify-email', [
                'name' => $notifiable->name,
                'verificationUrl' => $frontendUrl,
            ]);
    }
}
