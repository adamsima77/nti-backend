<?php

namespace Modules\Mentorship\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MilestoneStatus extends Model
{
    protected $table = 'milestone_status';

    protected $fillable = ['name'];

    public function milestones(): HasMany
    {
        return $this->hasMany(CallMilestone::class, 'milestone_status_id');
    }
}
