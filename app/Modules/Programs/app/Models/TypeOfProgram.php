<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeOfProgram extends Model
{
    use HasFactory;

    protected $table = 'type_of_program';

    protected $fillable = [
        'name',
    ];

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class, 'type_of_program_id');
    }
}
