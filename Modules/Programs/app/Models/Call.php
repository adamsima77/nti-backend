<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Organizations\Models\Organization;

class Call extends Model
{
    protected $table = 'call';

    protected $fillable = [
        'name',
        'application_deadline',
        'project_start',
        'project_end',
        'description',
        'program_id',
        'organization',
        'call_type',
        'active_status',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(StatusOfCall::class, 'active_status');
    }

    public function callCriteria(): BelongsToMany
    {
        return $this->belongsToMany(
            CallCriterion::class,
            'call_has_call_criterion',
            'call_id',
            'call_criterion_id'
        );
    }
}
