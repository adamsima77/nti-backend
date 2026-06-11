<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Models\EmailTemplate;
use Modules\Programs\Models\Call;

class CallPendingApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Call $call,
        public User $recipient,
        public int $languageId = 1,
    ) {}

    public function build(): self
    {
        $template = EmailTemplate::findBySlug('call_pending_approval')
            ?->forLanguage($this->languageId);

        $recipientName = trim(($this->recipient->name ?? '').' '.($this->recipient->surname ?? ''));

        $data = [
            'userName' => $recipientName !== '' ? $recipientName : ($this->recipient->email ?? ''),
            'callName' => $this->call->name ?? ('Výzva #'.$this->call->id),
        ];

        $renderedSubject = $template ? $template->renderSubject($data) : 'Výzva čaká na schválenie';
        $renderedBody    = $template ? $template->render($data) : '';

        return $this->subject($renderedSubject)
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $renderedSubject,
                'body_html' => $renderedBody,
            ]);
    }
}
