<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Modules\Content\Database\Factories\PartnerReferenceTranslationFactory;

class PartnerReferenceTranslation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'job_position',
        'description',
        'partner_reference_id',
        'language_id'
    ];

    public function partnerReference(): BelongsTo
    {
        return $this->belongsTo(PartnerReference::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    // protected static function newFactory(): PartnerReferenceTranslationFactory
    // {
    //     // return PartnerReferenceTranslationFactory::new();
    // }
}
