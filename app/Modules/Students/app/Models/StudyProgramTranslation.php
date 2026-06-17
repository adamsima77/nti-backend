<?php

namespace Modules\Students\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Content\Models\Language;

// use Modules\Students\Database\Factories\StudyProgramTranslationFactory;

class StudyProgramTranslation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'language_id',
        'study_program_id'
    ];

    public function language(): BelongsTo{
        return $this->belongsTo(Language::class);
    }

    public function studyProgram(): BelongsTo{
        return $this->belongsTo(StudyProgram::class);
    }

    // protected static function newFactory(): StudyProgramTranslationFactory
    // {
    //     // return StudyProgramTranslationFactory::new();
    // }
}
