<?php
namespace Modules\IdentityAccess\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends Notification
{
public function via($notifiable)
{
return ['mail'];
}

public function toMail($notifiable)
{
$url = url("/auth/verify-email/{$notifiable->id}/" . sha1($notifiable->getEmailForVerification()));

return (new MailMessage)
->subject('Verify your email address')
->greeting('Hello ' . $notifiable->name)
->line('Please verify your email address by clicking the button below.')
->action('Verify Email', $url)
->line('If you did not create this account, you can ignore this email.');
}
}
