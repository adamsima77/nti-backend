<?php

namespace Modules\IdentityAccess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// use Modules\IdentityAccess\Database\Factories\RoleFactory;

class Role extends Model
{

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name'
    ];

    public function users(): BelongsToMany{
        return $this->hasMany(User::class);
    }
}
