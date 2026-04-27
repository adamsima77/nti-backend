<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Support\Facades\Storage;
use Modules\Content\Database\Factories\PartnersFactory;

class Partner extends Model
{
    use SoftDeletes, HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'image'
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

    public function partnerTranslations(): HasMany
    {
        return $this->hasMany(PartnerTranslation::class);
    }

    protected static function newFactory(): PartnersFactory
    {
        return PartnersFactory::new();
    }
}

