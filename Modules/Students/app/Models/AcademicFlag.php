<?php

namespace Modules\Students\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// use Modules\Students\Database\Factories\AcademicFlagFactory;

class AcademicFlag extends Model
{
    use HasFactory;

    protected $table = 'academic_flags';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
    ];

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(
            Student::class,
            'student_has_academic_flags',
            'academic_flags_id',
            'student_id'
        );
    }
}
