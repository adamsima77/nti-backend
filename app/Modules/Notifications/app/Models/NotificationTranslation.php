<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Notifications\Database\Factories\NotificationTranslationFactory;

class NotificationTranslation extends Model
{
    use HasFactory;

    protected $table = 'notification_translation';

    protected static function newFactory(): NotificationTranslationFactory
    {
        return NotificationTranslationFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'notification_id',
        'language_id',
        'title',
        'body',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notifications::class, 'notification_id');
    }
}
