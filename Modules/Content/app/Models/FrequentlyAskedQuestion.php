<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FrequentlyAskedQuestion extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'page_id'
    ];

    public function frequentlyAskedQuestionTranslations(): HasMany{
        return $this->hasMany(FrequentlyAskedQuestionTranslation::class);
    }
}
