<?php

namespace Modules\IdentityAccess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsentType extends Model
{

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'consent_types';
    protected $fillable = [
        'name',
        'description'
    ];

    //Eloquent Relations
    public function userConsents(): HasMany{
        return $this->hasMany(UserConsent::class);
    }
}
