<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\IdentityAccess\Models\User;
use Modules\Notifications\Database\Factories\NotificationFactory;

class Notifications extends Model
{
    use HasFactory;

    protected $table = 'notification';

    protected static function newFactory(): NotificationFactory
    {
        return NotificationFactory::new();
    }

    protected $fillable = [
        'user_id',
        'notification_category_id',
        'notifiable_type',
        'notifiable_id',
        'title',
        'body',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(NotificationCategory::class, 'notification_category_id');
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function translations(): HasMany
    {
        return $this->hasMany(NotificationTranslation::class, 'notification_id');
    }


    public function forLanguage(?int $languageId): self
    {
        if ($languageId) {
            $translation = $this->translations
                ->firstWhere('language_id', $languageId);

            if ($translation) {
                $this->title = $translation->title;
                $this->body  = $translation->body;
            }
        }

        return $this;
    }
}
