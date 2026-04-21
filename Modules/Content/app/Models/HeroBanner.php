<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// use Modules\Content\Database\Factories\HeroBannerFactory;

class HeroBanner extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'page_id'
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function heroBannerTranslations(): HasMany
    {
        return $this->hasMany(HeroBannerTranslation::class);
    }

    // protected static function newFactory(): HeroBannerFactory
    // {
    //     // return HeroBannerFactory::new();
    // }
}
