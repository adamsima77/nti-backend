<?php

namespace Modules\Students\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Content\Models\Language;

// use Modules\Students\Database\Factories\StudyFieldTranslationFactory;

class StudyFieldTranslation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'language_id',
        'study_field_id'
    ];

    public function language(): BelongsTo{
        return $this->belongsTo(Language::class);
    }

    public function studyField(): BelongsTo{
        return $this->belongsTo(StudyField::class, 'study_field_id');
    }

    // protected static function newFactory(): StudyFieldTranslationFactory
    // {
    //     // return StudyFieldTranslationFactory::new();
    // }
}
