<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusOfCallHasCall extends Model
{
    protected $table = 'status_of_call_has_call';

    protected $fillable = [
        'call_id',
        'status_of_call_id',
        'note',
    ];

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class, 'call_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(StatusOfCall::class, 'status_of_call_id');
    }
}
