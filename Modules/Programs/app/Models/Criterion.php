<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Criterion extends Model
{
    protected $table = 'criterion';

    protected $fillable = [
    ];

    public function criterionTranslations(): HasMany{
        return $this->hasMany(CriterionTranslation::class);
    }
}
