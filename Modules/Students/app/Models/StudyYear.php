<?php

namespace Modules\Students\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

// use Modules\Students\Database\Factories\StudyYearFactory;

class StudyYear extends Model
{

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name'
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'study_year_id');
    }

    public function studyYearTranslations(): HasMany{
        return $this->hasMany(StudyYearTranslation::class);
    }

    // protected static function newFactory(): StudyYearFactory
    // {
    //     // return StudyYearFactory::new();
    // }
}
