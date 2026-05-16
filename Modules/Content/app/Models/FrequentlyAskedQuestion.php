<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function cmsStatus(): BelongsTo{
        return $this->belongsTo(CmsStatus::class);
    }

    public function page(): BelongsTo{
        return $this->belongsTo(Page::class);
    }
}
