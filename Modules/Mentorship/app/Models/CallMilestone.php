<?php

namespace Modules\Mentorship\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Applications\Models\Document;
use Modules\Programs\Models\Call;

class CallMilestone extends Model
{
    protected $table = 'milestone';

    protected $fillable = [
        'call_id',
        'name',
        'description',
        'due_date',
        'milestone_status_id',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

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
        return $this->hasMany(MilestoneComment::class, 'milestone_id')->with('user:id,name,surname')->orderBy('created_at');
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
