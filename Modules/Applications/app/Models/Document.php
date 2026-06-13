<?php

namespace Modules\Applications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\IdentityAccess\Models\User;
use Modules\Students\Models\AcademicRecord;

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

    public function latestVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class, 'document_id')
            ->ofMany('id', 'max');
    }


    public function securityClassification(): BelongsTo
    {
        return $this->belongsTo(SecurityClassification::class, 'security_classification_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'document_id');
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(
            Application::class,
            'document_has_application',
            'document_id',
            'application_id'
        );
    }

    public function academicRecords(): HasMany
    {
        return $this->hasMany(AcademicRecord::class, 'transcript_file');
    }
}
