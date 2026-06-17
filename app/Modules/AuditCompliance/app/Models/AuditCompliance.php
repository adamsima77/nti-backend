<?php

namespace Modules\AuditCompliance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\IdentityAccess\Models\User;

class AuditCompliance extends Model
{
    public $timestamps = false;

    protected $table = 'audit_event';

    protected $fillable = [
        'user_id',
        'action',
        'object_type',
        'object_id',
        'ip',
        'result',
        'result_payload',
        'time_of_action',
    ];

    protected function casts(): array
    {
        return [
            'result_payload' => 'array',
            'time_of_action' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function log(
        int $userId,
        string $action,
        string $objectType,
        int $objectId,
        string $ip,
        string $result,
        ?array $resultPayload = null,
    ): self {
        return self::create([
            'user_id'        => $userId,
            'action'         => $action,
            'object_type'    => $objectType,
            'object_id'      => $objectId,
            'ip'             => $ip,
            'result'         => $result,
            'result_payload' => $resultPayload,
            'time_of_action' => now(),
        ]);
    }
}
