<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\IdentityAccess\Models\User;

// use Modules\Content\Database\Factories\NewsTranslationFactory;

class NewsTranslation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'description',
        'news_id',
        'language_id'
    ];

    public function user(): BelongsTo{
        return $this->belongsTo(User::class);
    }

    public function news(): BelongsTo{
        return $this->belongsTo(News::class);
    }

    public function language(): BelongsTo{
        return $this->belongsTo(Language::class);
    }

    // protected static function newFactory(): NewsTranslationFactory
    // {
    //     // return NewsTranslationFactory::new();
    // }
}
