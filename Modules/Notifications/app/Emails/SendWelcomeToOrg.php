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
        public readonly Organization $organization
    ) {}

    public function build(): self
    {
        $languageId = request()->cookie('i18n_redirected', 'sk') === 'en' ? 2 : 1;

        $template = EmailTemplate::findBySlug('organization_account_approved')
            ?->forLanguage($languageId);;

        return $this->subject(
            $template?->subject ?? ('Your organization has been approved — ' . $this->organization->name)
        )
            ->view('notifications::emails.layout')
            ->with([
                'subject' => $template?->subject ?? '',

                'body_html' => $template?->render([
                        'organizationName' => $this->organization->name,
                    ]) ?? '',
            ]);
    }
}
