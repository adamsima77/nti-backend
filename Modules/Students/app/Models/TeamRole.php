<?php

namespace Modules\Students\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

// use Modules\Students\Database\Factories\TeamRoleFactory;

class TeamRole extends Model
{
    use HasFactory;

    protected $table = 'team_role';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name'
    ];

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class, 'team_role_id');
    }
}
