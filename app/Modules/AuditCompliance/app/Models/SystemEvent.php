<?php

namespace Modules\AuditCompliance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\IdentityAccess\Models\User;

// use Modules\AuditCompliance\Database\Factories\SystemEventFactory;

class SystemEvent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */

    public $timestamps = false;

    protected $fillable = [
        'event_type',
        'severity',
        'message',
        'stack_trace',
        'context',
        'user_id',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo{
        return $this->belongsTo(User::class, 'user_id');
    }

    // protected static function newFactory(): SystemEventFactory
    // {
    //     // return SystemEventFactory::new();
    // }
}
