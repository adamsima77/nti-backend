<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class BulkEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $renderedSubject;
    public string $renderedBody;
    public function __construct(string $subject, string $body)
    {
        $this->renderedSubject = $subject;
        $this->renderedBody = $body;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject($this->renderedSubject)
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $this->renderedSubject,
                'body_html' => $this->renderedBody,
            ]);
    }
}
