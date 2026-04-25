<?php

namespace Modules\Organizations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

// use Modules\Organizations\Database\Factories\OrganizationRoleFactory;

class OrganizationRole extends Model
{
    use HasFactory;

    protected $table = 'organization_role';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
    ];

    public function userOrganizations(): HasMany
    {
        return $this->hasMany(UserOrganization::class, 'organization_role');
    }
}
