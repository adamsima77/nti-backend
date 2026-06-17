<?php

namespace Modules\Applications\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Applications\Models\Application;
use Modules\IdentityAccess\Models\User;

class ApplicationStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Application $application,
        public string $newStatus,
        public ?string $note,
        public ?User $changedBy,
        public int $languageId,
    ) {}
}
