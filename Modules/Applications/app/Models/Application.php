<?php

namespace Modules\Applications\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    use HasFactory;

    protected $table = 'application';

    public $timestamps = false;

    protected $fillable = [
        'submitted_at',
        'last_update',
        'call_id',
        'team_id',
        'created_by',
        'active_status',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'last_update' => 'datetime',
    ];

    public function status(): BelongsTo
    {
        return $this->belongsTo(StatusOfApplication::class, 'active_status');
    }
}
