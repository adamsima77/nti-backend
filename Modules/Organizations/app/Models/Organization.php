<?php

namespace Modules\Organizations\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\IdentityAccess\Models\User;

class Organization extends Model
{
    use HasFactory;

    protected $table = 'organization';

    protected $fillable = [
        'name',
        'phone',
        'ico',
        'web_url',
        'address_id',
    ];

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_organization', 'organization_id', 'user_id')
            ->using(UserOrganization::class)
            ->withPivot('organization_role');
    }
}
