<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Content\Models\Language;

// use Modules\Programs\Database\Factories\CriterionTranslationFactory;

class CriterionTranslation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'language_id',
        'criterion_id',
        'description'
    ];

    public function criterion(): BelongsTo{
        return $this->belongsTo(Criterion::class);
    }

    public function language(): BelongsTo{
        return $this->belongsTo(Language::class);
    }

    // protected static function newFactory(): CriterionTranslationFactory
    // {
    //     // return CriterionTranslationFactory::new();
    // }
}
