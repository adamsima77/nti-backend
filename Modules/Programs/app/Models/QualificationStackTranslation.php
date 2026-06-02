<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Content\Models\Language;

// use Modules\Programs\Database\Factories\QualificationStackTranslationFactory;

class QualificationStackTranslation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'language_id',
        'qualification_stack_id'
    ];

    public function qualification_stack(): BelongsTo{
        return $this->belongsTo(QualificationStack::class);
    }

    public function language(): BelongsTo{
        return $this->belongsTo(Language::class);
    }

    // protected static function newFactory(): QualificationStackTranslationFactory
    // {
    //     // return QualificationStackTranslationFactory::new();
    // }
}
