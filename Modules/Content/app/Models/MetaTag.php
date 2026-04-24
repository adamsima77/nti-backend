<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

// use Modules\Content\Database\Factories\MetaTagFactory;

class MetaTag extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'page_id'
    ];

    public function metaTagTranslations(): HasMany{
        return $this->hasMany(MetaTagTranslation::class);
    }
    // protected static function newFactory(): MetaTagFactory
    // {
    //     // return MetaTagFactory::new();
    // }
}
