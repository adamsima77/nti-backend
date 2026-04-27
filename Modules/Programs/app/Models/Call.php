<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'organization_id',
        'call_type_id',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function callType(): BelongsTo
    {
        return $this->belongsTo(CallType::class, 'call_type_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(StatusOfCallHasCall::class, 'call_id');
    }

    public function currentStatusHistory(): HasOne
    {
        return $this->hasOne(StatusOfCallHasCall::class, 'call_id')->latestOfMany('id');
    }

    public function callCriteria(): BelongsToMany
    {
        return $this->belongsToMany(
            Criterion::class,
            'call_has_criterion',
            'call_id',
            'criterion_id'
        );
    }
}
