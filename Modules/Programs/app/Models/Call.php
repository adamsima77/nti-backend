<?php

namespace Modules\Programs\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Applications\Models\Application;
use Modules\IdentityAccess\Models\User;
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
        'application_form_schema',
        'program_id',
        'organization_id',
        'call_type_id',
        'application_start',
        'budget',
        'tech_spec',
        'tech_tags',
        'max_teams',
        'budget_type',
        'po_user_id'
    ];

    protected $casts = [
        'application_form_schema' => 'array',
        'tech_tags' => 'array',
        'budget' => 'decimal:2',
        'max_teams' => 'integer',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function callTranslations(): HasMany{
        return $this->hasMany(CallTranslation::class, 'call_id');
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

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'call_id');
    }

    public function formSchemas(): HasMany
    {
        return $this->hasMany(FormSchema::class, 'call_id');
    }

    public function productOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'po_user_id');
    }
}
