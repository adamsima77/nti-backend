<?php

namespace Modules\Mentorship\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\IdentityAccess\Models\User;

class MilestoneComment extends Model
{
    protected $table = 'milestone_comments';

    protected $fillable = [
        'milestone_id',
        'user_id',
        'parent_comment_id',
        'comment_text',
    ];

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(CallMilestone::class, 'milestone_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
