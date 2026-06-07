<?php

namespace Modules\Mentorship\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Programs\Models\Call;

class CallMilestone extends Model
{
    protected $table = 'milestone';

    protected $fillable = [
        'call_id',
        'name',
        'description',
        'due_date',
        'status',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class, 'call_id');
    }
}
