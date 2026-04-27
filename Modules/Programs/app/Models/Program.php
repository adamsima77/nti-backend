<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory;

    protected $table = 'program';

    protected $fillable = [
        'name',
        'type_of_program_id',
        'description',
    ];

    public function typeOfProgram(): BelongsTo
    {
        return $this->belongsTo(TypeOfProgram::class, 'type_of_program_id');
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class, 'program_id');
    }
}
