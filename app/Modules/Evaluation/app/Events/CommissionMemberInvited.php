<?php

namespace Modules\Evaluation\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Evaluation\Models\Commission;
use Modules\Evaluation\Models\CommissionInvitation;

class CommissionMemberInvited
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CommissionInvitation $invitation,
        public readonly Commission $commission,
        public readonly string $lang = 'sk',
    ) {}
}
