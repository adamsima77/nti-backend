<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Modules\Content\Database\Factories\NewsFactory;
use Modules\IdentityAccess\Models\User;
class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'category_id',
        'user_id',
        'image'
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        return Storage::url($this->image);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function newsTranslations(): HasMany
    {
        return $this->hasMany(NewsTranslation::class);
    }

    protected static function newFactory(): NewsFactory
    {
        return NewsFactory::new();
    }
}
