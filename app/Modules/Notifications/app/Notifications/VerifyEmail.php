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

    public function __construct(
        public int $languageId
    ) {}

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

        $localeCode = $this->languageId === 2 ? 'en' : null;

        $frontendUrl = rtrim(config('app.frontend_url'), '/')
            . ($localeCode ? "/$localeCode" : '')
            . '/auth/verify-email'
            . '/' . $notifiable->id
            . '/' . $hash
            . '?expires='   . ($params['expires'] ?? '')
            . '&signature=' . urlencode($params['signature'] ?? '');

        $template = EmailTemplate::findBySlug('verify_email')
            ?->forLanguage($this->languageId);;

        $data = [
            'name'            => $notifiable->name ?? $notifiable->email,
            'verificationUrl' => $frontendUrl,
        ];

        $renderedSubject = $template ? $template->renderSubject($data) : 'Verify your email address';
        $renderedBody    = $template ? $template->render($data) : '';

        return (new MailMessage)
            ->subject($renderedSubject)
            ->view('notifications::emails.layout', [
                'subject'   => $renderedSubject,
                'body_html' => $renderedBody,
            ]);
    }
}
