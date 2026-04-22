<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\IdentityAccess\Models\User;

// use Modules\Content\Database\Factories\NewsTranslationFactory;

class NewsTranslation extends Model
{
    protected $fillable = [
        'title',
        'description',
        'news_id',
        'language_id'
    ];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }
}
