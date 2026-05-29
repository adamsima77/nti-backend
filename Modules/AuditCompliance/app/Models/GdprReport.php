<?php

namespace Modules\AuditCompliance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Applications\Models\Document;
use Modules\IdentityAccess\Models\User;

// use Modules\AuditCompliance\Database\Factories\GdprReportFactory;

class GdprReport extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'requested_by',
        'attachment_id',
        'status', // pending, completed, failed, expired
        'expires_at',
        'downloaded_at'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the administrator who requested this generation.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the document attachment record linked to this report.
     */
    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'attachment_id');
    }

    // protected static function newFactory(): GdprReportFactory
    // {
    //     // return GdprReportFactory::new();
    // }
}
