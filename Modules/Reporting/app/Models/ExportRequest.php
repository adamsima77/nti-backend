<?php

namespace Modules\Reporting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\IdentityAccess\Models\User;

class ExportRequest extends Model
{
    protected $fillable = [
        'user_id',
        'export_key',
        'kind',
        'format',
        'status',
        'file_name',
        'storage_disk',
        'storage_path',
        'meta',
        'error_message',
        'queued_at',
        'processed_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'queued_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}