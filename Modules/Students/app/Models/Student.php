<?php

namespace Modules\Students\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\IdentityAccess\Models\User;

// use Modules\Students\Database\Factories\StudentFactory;

class Student extends Model
{
    use HasFactory;

    protected $table = 'student';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'study_program_id',
        'study_field_id',
        'university_id',
        'cv_document_id',
        'year_of_study',
        'portfolio_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'study_program_id');
    }

    public function studyField(): BelongsTo
    {
        return $this->belongsTo(StudyField::class, 'study_field_id');
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class, 'university_id');
    }

    public function academicFlags(): BelongsToMany
    {
        return $this->belongsToMany(
            AcademicFlag::class,
            'student_has_academic_flags',
            'student_id',
            'academic_flags_id'
        );
    }
}
