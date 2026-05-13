<?php

namespace Modules\Notifications\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Notifications\Models\EmailTemplate;
use Modules\Organizations\Models\Organization;

class NotifyAdminOrgOnboard extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Organization $org,
        public readonly string $email,
        public int $languageId
    ) {}

    public function build(): self
    {
        $template = EmailTemplate::findBySlug('admin_notification_organization_onboarded')
            ?->forLanguage($this->languageId);

        $data = [
            'organizationName' => $this->org->name,
            'ico'              => $this->org->ico,
            'sector'           => $this->org->sectors?->pluck('name')->join(', ') ?? '',
            'address'          => $this->formatAddress(),
            'contactEmail'     => $this->email,
            'registeredAt'     => now()->format('d.m.Y H:i'),
            'organizationId'   => $this->org->id,
        ];

        $renderedSubject = $template ? $template->renderSubject($data) : ('New organization pending approval — ' . $this->org->name);
        $renderedBody    = $template ? $template->render($data) : '';

        return $this->subject($renderedSubject)
            ->view('notifications::emails.layout')
            ->with([
                'subject'   => $renderedSubject,
                'body_html' => $renderedBody,
            ]);
    }

    private function formatAddress(): string
    {
        if (!$this->org->address) {
            return '';
        }

        return trim(
            ($this->org->address->street ?? '') . ', ' .
            ($this->org->address->city ?? '')
            , ', ');
    }
}
