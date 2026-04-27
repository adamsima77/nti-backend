<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

// use Modules\Content\Database\Factories\SiteMemberFactory;

class SiteMember extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name'
    ];

    public function siteMemberTranslations(): HasMany{
        return $this->hasMany(SiteMemberTranslation::class);
    }

    // protected static function newFactory(): SiteMemberFactory
    // {
    //     // return SiteMemberFactory::new();
    // }
}
