<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Criterion extends Model
{
    protected $table = 'criterion';

    protected $fillable = ['code'];

    public function criterionTranslations(): HasMany{
        return $this->hasMany(CriterionTranslation::class);
    }

    public function calls(): BelongsToMany
    {
        return $this->belongsToMany(Call::class, 'call_criterion');
    }
}
