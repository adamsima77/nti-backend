<?php

namespace Modules\IdentityAccess\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
// use Modules\IdentityAccess\Database\Factories\PermissionFactory;

class Permission extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name'
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role',
            'permission_id',
            'role_id'
        );
    }



    // protected static function newFactory(): PermissionFactory
    // {
    //     // return PermissionFactory::new();
    // }
}
