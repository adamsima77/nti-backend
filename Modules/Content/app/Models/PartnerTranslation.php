<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Modules\Content\Database\Factories\PartnerTranslationFactory;

class PartnerTranslation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'partner_id',
        'language_id'
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    // protected static function newFactory(): PartnerTranslationFactory
    // {
    //     // return PartnerTranslationFactory::new();
    // }
}
