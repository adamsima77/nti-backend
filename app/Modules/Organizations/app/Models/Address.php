<?php

namespace Modules\Organizations\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Address extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Modules\Organizations\Database\Factories\AddressFactory::new();
    }

    protected $table = 'address';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'city',
        'street',
        'postal_code',
        'country',
    ];

    public function organization(): HasOne
    {
        return $this->hasOne(Organization::class, 'address_id');
    }
}
