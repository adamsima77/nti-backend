<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Programs\Database\Factories\ProgramTranslationFactory;

class ProgramTranslation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'program_id',
        'language_id'
    ];

    public function program(): BelongsTo{
        return $this->belongsTo(Program::class);
    }

    // protected static function newFactory(): ProgramTranslationFactory
    // {
    //     // return ProgramTranslationFactory::new();
    // }
}
