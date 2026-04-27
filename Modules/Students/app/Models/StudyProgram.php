<?php

namespace Modules\Students\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

// use Modules\Students\Database\Factories\StudyProgramFactory;

class StudyProgram extends Model
{
    use HasFactory;

    protected $table = 'study_program';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
    ];

    public function studentProfiles(): HasMany
    {
        return $this->hasMany(Student::class, 'study_program_id');
    }
}
