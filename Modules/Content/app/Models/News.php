<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Content\Database\Factories\NewsFactory;

class News extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'slug',
        'category_id',
        'user_id',
    ];


    public function newsTranslations(): HasMany{
        return $this->hasMany(NewsTranslation::class);
    }
    protected static function newFactory(): NewsFactory
     {
         return NewsFactory::new();
     }
}
