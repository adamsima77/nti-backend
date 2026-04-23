<?php
namespace Modules\IdentityAccess\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
class VerifyEmail extends Notification
{
public function via($notifiable)
{
    return ['mail'];
}

    public function toMail($notifiable)
    {
        $verificationUrl = URL::temporarySignedRoute(
            'api.verification.verify',
            now()->addMinutes(15),
            [
                'id' => $notifiable->id,
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        $frontendUrl = config('app.frontend_url') . '/auth/verify-email?url=' . urlencode($verificationUrl);

        return (new MailMessage)
            ->subject('Verify your email address')
            ->greeting('Hello ' . $notifiable->name)
            ->line('Please verify your email address by clicking the button below.')
            ->action('Verify Email', $frontendUrl)
            ->line('If you did not create this account, you can ignore this email.');
    }
}
