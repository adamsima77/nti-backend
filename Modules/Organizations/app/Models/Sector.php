<?php

namespace Modules\Organizations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// use Modules\Organizations\Database\Factories\SectorFactory;

class Sector extends Model
{
    use HasFactory;

    protected $table = 'sector';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
    ];

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(
            Organization::class,
            'sector_has_organization',
            'sector_id',
            'organization_id'
        );
    }
}
