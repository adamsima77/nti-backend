<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Content\Database\Factories\MetaTagTranslationFactory;

class MetaTagTranslation extends Model
{
    protected $fillable = [
        'title',
        'description',
        'og_title',
        'og_description',
        'og_type',
        'og_url',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'meta_tag_id',
        'language_id'
    ];

    public function metaTagTranslation(): BelongsTo{
        return $this->belongsTo(MetaTag::class);
    }

    // protected static function newFactory(): MetaTagTranslationFactory
    // {
    //     // return MetaTagTranslationFactory::new();
    // }
}
