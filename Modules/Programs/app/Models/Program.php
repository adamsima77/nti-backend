<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Program extends Model
{
    use HasFactory;

    protected $table = 'program';

    protected $fillable = [
        'name',
        'type_of_program',
        'description',
    ];

    public function typeOfProgram(): BelongsTo
    {
        return $this->belongsTo(TypeOfProgram::class, 'type_of_program');
    }
}
