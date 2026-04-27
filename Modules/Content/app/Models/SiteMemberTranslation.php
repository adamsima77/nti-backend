<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Content\Database\Factories\SiteMemberTranslationFactory;

class SiteMemberTranslation extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'job_position',
        'site_member_id',
        'language_id'
    ];

    public function language(): BelongsTo{
        return $this->belongsTo(Language::class);
    }

    public function siteMember(): BelongsTo{
        return $this->belongsTo(SiteMember::class);
    }

    // protected static function newFactory(): SiteMemberTranslationFactory
    // {
    //     // return SiteMemberTranslationFactory::new();
    // }
}
