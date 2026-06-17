<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Modules\Content\Database\Factories\PartnerReferenceFactory;

class PartnerReference extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'status_id',
        'image',
        'name',
        'job_position'
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        return Storage::url($this->image);
    }
    public function partnerReferenceTranslations(): HasMany
    {
        return $this->hasMany(PartnerReferenceTranslation::class);
    }

    public function cmsStatus(): BelongsTo{
        return $this->belongsTo(CmsStatus::class);
    }

    public function page(): BelongsTo{
        return $this->belongsTo(Page::class);
    }

    protected static function newFactory(): PartnerReferenceFactory
    {
        return PartnerReferenceFactory::new();
    }
}
