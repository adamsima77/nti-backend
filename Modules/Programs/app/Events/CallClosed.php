<?php

namespace Modules\Programs\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Programs\Models\Call;

class CallClosed
{
    use Dispatchable, SerializesModels;

    public function __construct(public Call $call) {}
}
