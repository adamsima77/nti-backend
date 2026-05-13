<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Organizations\Models\SectorTranslation;
use Modules\Programs\Models\CallTranslation;
use Modules\Programs\Models\CriterionTranslation;

// use Modules\Content\Database\Factories\LanguageFactory;

class Language extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name'
    ];

    public function categoryTranslations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function newsTranslations(): HasMany
    {
        return $this->hasMany(NewsTranslation::class);
    }

    public function callTranslations(): HasMany{
        return $this->hasMany(CallTranslation::class);
    }

    public function heroBannerTranslations(): HasMany
    {
        return $this->hasMany(HeroBannerTranslation::class);
    }

    public function partnerTranslations(): HasMany
    {
        return $this->hasMany(PartnerTranslation::class);
    }

    public function partnerReferenceTranslations(): HasMany
    {
        return $this->hasMany(PartnerReferenceTranslation::class);
    }

    public function sectorTranslations(): HasMany{
        return $this->hasMany(SectorTranslation::class);
    }

    public function criterionTranslations(): HasMany{
        return $this->hasMany(CriterionTranslation::class);
    }

    // protected static function newFactory(): LanguageFactory
    // {
    //     // return LanguageFactory::new();
    // }
}
