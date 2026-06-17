<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

// use Modules\Content\Database\Factories\CmsStatusFactory;

class CmsStatus extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name'
    ];

    public function heroBanners(): HasMany{
        return $this->hasMany(HeroBanner::class);
    }

    public function metaTags(): HasMany{
        return $this->hasMany(MetaTag::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(FrequentlyAskedQuestion::class);
    }

    public function partners(): HasMany{
        return $this->hasMany(Partner::class);
    }

    public function partnerReferences(): HasMany{
        return $this->hasMany(PartnerReference::class);
    }

    public function siteMembers(): HasMany{
        return $this->hasMany(SiteMember::class);
    }

    public function news(): HasMany{
        return $this->hasMany(News::class);
    }

    // protected static function newFactory(): CmsStatusFactory
    // {
    //     // return CmsStatusFactory::new();
    // }
}
