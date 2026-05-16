<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Content\Database\Factories\PartnerReferenceFactory;

class PartnerReference extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    public function partnerReferenceTranslations(): HasMany
    {
        return $this->hasMany(PartnerReferenceTranslation::class);
    }

    public function cmsStatus(): BelongsTo{
        return $this->belongsTo(CmsStatus::class);
    }

    protected static function newFactory(): PartnerReferenceFactory
    {
        return PartnerReferenceFactory::new();
    }
}
