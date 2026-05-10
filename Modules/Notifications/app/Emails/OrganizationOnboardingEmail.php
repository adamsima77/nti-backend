<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Models\EmailTemplate;
use Modules\Organizations\Models\Organization;

class OrganizationOnboardingEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public Organization $org;
    public String $email;
    public function __construct(Organization $org, String $email) {
        $this->org = $org;
        $this->email = $email;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $template = EmailTemplate::findBySlug('organization_onboarded');

        return $this->subject($template?->subject ?? 'Thank you for registering!')
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $template?->subject ?? '',
                'body_html' => $template?->render([
                        'organizationName' => $this->org->name,
                    ]) ?? '',
            ]);
    }
}
