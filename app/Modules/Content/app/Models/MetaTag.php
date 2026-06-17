<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Modules\Content\Models\MetaTagTranslation;

// use Modules\Content\Database\Factories\MetaTagFactory;

class MetaTag extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'page_id',
        'image',
        'status_id'
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

    public function cmsStatus(): BelongsTo{
        return $this->belongsTo(CmsStatus::class);
    }

    public function metaTagTranslations(): HasMany{
        return $this->hasMany(MetaTagTranslation::class, 'meta_tag_id');
    }

    public function page(): BelongsTo{
        return $this->belongsTo(Page::class);
    }
    // protected static function newFactory(): MetaTagFactory
    // {
    //     // return MetaTagFactory::new();
    // }
}
