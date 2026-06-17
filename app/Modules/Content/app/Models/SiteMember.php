<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

// use Modules\Content\Database\Factories\SiteMemberFactory;

class SiteMember extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'job_position',
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
        return $this->belongsTo(CmsStatus::class, 'status_id');
    }

    // protected static function newFactory(): SiteMemberFactory
    // {
    //     // return SiteMemberFactory::new();
    // }
}
