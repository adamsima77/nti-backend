<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Notifications\Database\Factories\NotificationCategoryTranslationFactory;

// use Modules\Notifications\Database\Factories\NotificationCategoryTranslationFactory;

class NotificationCategoryTranslation extends Model
{
    use HasFactory;

    protected $table = 'notification_category_translation';

    protected static function newFactory(): NotificationCategoryTranslationFactory
    {
        return NotificationCategoryTranslationFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'notification_category_id',
        'language_id',
        'name',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(NotificationCategory::class, 'notification_category_id');
    }
}
