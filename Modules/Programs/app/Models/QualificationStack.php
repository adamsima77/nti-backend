<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

// use Modules\Programs\Database\Factories\QualificationStackFactory;

class QualificationStack extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
    ];

    public function calls():HasMany{
        return $this->hasMany(Call::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(QualificationStackTranslation::class);
    }

    // protected static function newFactory(): QualificationStackFactory
    // {
    //     // return QualificationStackFactory::new();
    // }
}
