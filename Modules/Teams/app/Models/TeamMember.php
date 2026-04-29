<?php

namespace Modules\Teams\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\IdentityAccess\Models\User;
use Modules\Teams\Database\Factories\TeamMemberFactory;

class TeamMember extends Model
{
    use HasFactory;

    protected $table = 'team_members';

    public $incrementing = false;
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'team_id',
        'team_role_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(TeamRole::class, 'team_role_id');
    }

    protected static function newFactory(): TeamMemberFactory
    {
        return TeamMemberFactory::new();
    }
}
