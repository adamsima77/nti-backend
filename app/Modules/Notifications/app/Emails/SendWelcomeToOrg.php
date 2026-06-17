<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Notifications\Models\EmailTemplate;
use Modules\Organizations\Models\Organization;

class SendWelcomeToOrg extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Organization $organization,
        public int $languageId
    ) {}

    public function build(): self
    {

        $template = EmailTemplate::findBySlug('organization_account_approved')
            ?->forLanguage($this->languageId);

        $data = [
            'organizationName' => $this->organization->name,
        ];

        $renderedSubject = $template
            ? $template->renderSubject($data)
            : ('Your organization has been approved — ' . $this->organization->name);


        $renderedBody = $template
            ? $template->render($data)
            : "<p>Hello, your organization <strong>{$this->organization->name}</strong> has been successfully approved.</p>";

        return $this->subject($renderedSubject)
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $renderedSubject,
                'body_html' => $renderedBody,
            ]);
    }
}
