<?php

namespace Modules\Students\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Students\Database\Factories\UniversityFactory;

class University extends Model
{
    use HasFactory;

    protected $table = 'university';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'university_id');
    }

    protected static function newFactory(): UniversityFactory
    {
        return UniversityFactory::new();
    }
}
