<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Content\Models\Language;

// use Modules\Programs\Database\Factories\CallTranslationFactory;

class CallTranslation extends Model
{

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'call_id',
        'language_id'
    ];

    public function call(): BelongsTo{
        return $this->belongsTo(Call::class, 'call_id');
    }

    public function language(): BelongsTo{
        return $this->belongsTo(Language::class, 'language_id');
    }

    // protected static function newFactory(): CallTranslationFactory
    // {
    //     // return CallTranslationFactory::new();
    // }
}
