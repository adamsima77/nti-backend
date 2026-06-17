<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Notifications\Database\Factories\NotificationCategoryFactory;

class NotificationCategory extends Model
{
    use HasFactory;

    protected $table = 'notification_category';

    protected static function newFactory(): NotificationCategoryFactory
    {
        return NotificationCategoryFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'slug',
        'name',
        'icon',
        'color',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(NotificationCategoryTranslation::class, 'notification_category_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notifications::class, 'notification_category_id');
    }

    public function emailTemplates(): HasMany
    {
        return $this->hasMany(EmailTemplate::class, 'notification_category_id');
    }
}
