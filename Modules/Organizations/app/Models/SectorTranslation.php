<?php

namespace Modules\Organizations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Content\Models\Language;

// use Modules\Organizations\Database\Factories\SectorTranslationFactory;

class SectorTranslation extends Model
{

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'sector_id',
        'language_id'
    ];

    public function language(): BelongsTo{
        return $this->belongsTo(Language::class);
    }

    public function sector(): BelongsTo{
        return $this->belongsTo(Sector::class);
    }

    // protected static function newFactory(): SectorTranslationFactory
    // {
    //     // return SectorTranslationFactory::new();
    // }
}
