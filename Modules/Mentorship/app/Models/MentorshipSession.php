<?php

namespace Modules\Mentorship\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\IdentityAccess\Models\User;

class MentorshipSession extends Model
{
    protected $table = 'mentorship_session';

    protected $fillable = [
        'mentorship_id',
        'created_by',
        'date',
        'notes',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function mentorship(): BelongsTo
    {
        return $this->belongsTo(Mentorship::class, 'mentorship_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}