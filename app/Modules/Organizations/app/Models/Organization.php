<?php

namespace Modules\Organizations\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Models\Call;
use Propaganistas\LaravelPhone\Casts\E164PhoneNumberCast;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory()
    {
        return \Modules\Organizations\Database\Factories\OrganizationFactory::new();
    }

    protected $table = 'organization';

    protected $fillable = [
        'name',
        'phone',
        'ico',
        'web_url',
        'description',
        'address_id',
    ];

    protected $casts = [
        'phone' => E164PhoneNumberCast::class,
    ];

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_organization',
            'organization_id',
            'user_id'
        )
            ->using(UserOrganization::class)
            ->withPivot('organization_role');
    }

    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(
            Sector::class,
            'sector_has_organization',
            'organization_id',
            'sector_id'
        );
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class, 'organization_id');
    }
}
