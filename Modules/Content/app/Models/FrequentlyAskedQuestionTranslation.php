<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Content\Database\Factories\FrequentlyAskedQuestionTranslationFactory;

class FrequentlyAskedQuestionTranslation extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'question',
        'answer',
        'frequently_asked_question_id',
        'language_id'
    ];

    public function frequentlyAskedQuestion(): BelongsTo{
        return $this->belongsTo(FrequentlyAskedQuestion::class);
    }

    // protected static function newFactory(): FrequentlyAskedQuestionTranslationFactory
    // {
    //     // return FrequentlyAskedQuestionTranslationFactory::new();
    // }
}
