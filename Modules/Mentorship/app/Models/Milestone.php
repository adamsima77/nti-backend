<?php

namespace Modules\Mentorship\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\Document;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Models\Call;

class Milestone extends Model
{
    use HasFactory;

    protected $table = 'project_milestones';

    protected $fillable = [
        'name',
        'description',
        'deadline',
        'status',
        'comments',
        'call_id',
        'start_date',
        'milestone_status_id',
    ];

    protected $casts = [
        'deadline'   => 'date',
        'start_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class, 'call_id');
    }

    public function milestoneStatus(): BelongsTo
    {
        return $this->belongsTo(MilestoneStatus::class, 'milestone_status_id');
    }

    public function comments(): HasMany
    {

        return $this->hasMany(MilestoneComment::class, 'milestone_id', 'id');
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            Document::class,
            'document_has_milestone',
            'milestone_id',
            'document_id'
        )->with('versions');
    }


}
