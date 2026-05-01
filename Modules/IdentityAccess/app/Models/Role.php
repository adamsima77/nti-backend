<?php

namespace Modules\IdentityAccess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// use Modules\IdentityAccess\Database\Factories\RoleFactory;

class Role extends Model
{

    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name'
    ];

    public function users(): BelongsToMany {
        return $this->belongsToMany(User::class, 'role_user', 'role_id', 'user_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role',
            'role_id', 'permission_id'
        );
    }
}
