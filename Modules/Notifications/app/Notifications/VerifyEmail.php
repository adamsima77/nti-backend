<?php

namespace Modules\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Modules\Notifications\Models\EmailTemplate;

class VerifyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $hash = sha1($notifiable->getEmailForVerification());

        $signedUrl = URL::temporarySignedRoute(
            'api.verification.verify',
            now()->addMinutes(60),
            [
                'id'   => $notifiable->id,
                'hash' => $hash,
            ]
        );


        $queryString = parse_url($signedUrl, PHP_URL_QUERY) ?? '';
        parse_str($queryString, $params);

        $template = EmailTemplate::findBySlug('verify_email');

        $frontendUrl = rtrim(config('app.frontend_url'), '/')
            . '/auth/verify-email'
            . '/' . $notifiable->id
            . '/' . $hash
            . '?expires='   . ($params['expires'] ?? '')
            . '&signature=' . urlencode($params['signature'] ?? '');

        return (new MailMessage)
            ->subject($template?->subject ?? 'Verify your email address')
            ->view('notifications::emails.layout', [
                'subject' => $template?->subject ?? '',
                'body_html' => $template?->render([
                        'name' => $notifiable->name ?? $notifiable->email,
                        'verificationUrl' => $frontendUrl,
                    ]) ?? '',
            ]);
    }
}
