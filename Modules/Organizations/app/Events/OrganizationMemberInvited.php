<?php

namespace Modules\Organizations\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Organizations\Models\Organization;
use Modules\Organizations\Models\OrganizationInvitation;

class OrganizationMemberInvited
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly OrganizationInvitation $invitation,
        public readonly Organization $organization,
        public readonly string $roleLabel,
        public readonly string $lang = 'sk',
    ) {}
}
