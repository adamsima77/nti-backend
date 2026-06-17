<?php

namespace Modules\Mentorship\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Applications\Models\Application;
use Modules\IdentityAccess\Models\User;

class Mentorship extends Model
{
    protected $table = 'mentorship';

    protected $fillable = [
        'mentor_user_id',
        'application_id',
    ];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_user_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(MentorshipSession::class, 'mentorship_id');
    }
}
