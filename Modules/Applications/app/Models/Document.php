<?php

namespace Modules\Applications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\IdentityAccess\Models\User;

class Document extends Model
{
    protected $table = 'document';

    protected $fillable = [
        'owner_id',
        'security_classification_id',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function securityClassification(): BelongsTo
    {
        return $this->belongsTo(SecurityClassification::class, 'security_classification_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'document_id');
    }
}
