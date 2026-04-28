<?php

namespace Modules\Students\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Students\Database\Factories\StudyFieldFactory;

class StudyField extends Model
{
    use HasFactory;

    protected $table = 'study_field';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'study_field_id');
    }

    protected static function newFactory(): StudyFieldFactory
    {
        return StudyFieldFactory::new();
    }
}
