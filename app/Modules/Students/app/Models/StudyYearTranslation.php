<?php

namespace Modules\Students\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Content\Models\Language;

// use Modules\Students\Database\Factories\StudyYearTranslationFactory;

class StudyYearTranslation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'language_id',
        'study_year_id'
    ];

    public function language(): BelongsTo{
        return $this->belongsTo(Language::class);
    }

    public function studyYear(): BelongsTo{
        return $this->belongsTo(StudyYear::class);
    }

    // protected static function newFactory(): StudyYearTranslationFactory
    // {
    //     // return StudyYearTranslationFactory::new();
    // }
}
