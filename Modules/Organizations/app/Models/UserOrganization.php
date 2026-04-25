<?php

namespace Modules\Organizations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\IdentityAccess\Models\User;

// use Modules\Organizations\Database\Factories\UserOrganizationFactory;

class UserOrganization extends Model
{
    use HasFactory;

    protected $table = 'user_organization';

    public $incrementing = false;

    protected $primaryKey = ['user_id', 'organization_id'];

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'organization_id',
        'organization_role',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(OrganizationRole::class, 'organization_role');
    }
}
