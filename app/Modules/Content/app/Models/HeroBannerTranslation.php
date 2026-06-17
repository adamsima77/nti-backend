<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Content\Database\Factories\HeroBannerTranslationFactory;

class HeroBannerTranslation extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'description',
        'hero_banner_id',
        'language_id'
    ];

    public function heroBanner(): BelongsTo{
        return $this->belongsTo(HeroBanner::class);
    }

    public function language(): BelongsTo{
        return $this->belongsTo(Language::class);
    }

    // protected static function newFactory(): HeroBannerTranslationFactory
    // {
    //     // return HeroBannerTranslationFactory::new();
    // }
}
