<?php

namespace Modules\Students\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\IdentityAccess\Models\User;

// use Modules\Students\Database\Factories\TeamFactory;

class Team extends Model
{
    use HasFactory;

    protected $table = 'team';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'team_members',
            'team_id',
            'user_id'
        )
            ->using(TeamMember::class)
            ->withPivot('team_role_id');
    }
}
