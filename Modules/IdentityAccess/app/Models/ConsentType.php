<?php

namespace Modules\IdentityAccess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsentType extends Model
{
    use SoftDeletes;
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
