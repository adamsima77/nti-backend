<?php

namespace Modules\Applications\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Evaluation\Models\Evaluation;
use Modules\Mentorship\Models\Milestone;
use Modules\Mentorship\Models\Mentorship;
use Modules\IdentityAccess\Models\User;
use Modules\Programs\Models\Call;
use Modules\Programs\Models\FormSchema;
use Modules\Reporting\Models\ProjectKpi;
use Modules\Reporting\Models\ProjectOutput;
use Modules\Teams\Models\Team;
use Modules\Content\Models\Category;

class Application extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'application';

    public $timestamps = false;

    protected $fillable = [
        'submitted_at',
        'last_update',
        'call_id',
        'team_id',
        'created_by',
        'active_status',
        'category_id',
        'form_data',
        'form_schema_id',
        'reference'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'last_update' => 'datetime',
        'form_data' => 'array',
    ];

    protected $appends = [
        'academic_flag',
    ];


    public function status(): BelongsTo
    {
        return $this->belongsTo(StatusOfApplication::class, 'active_status');
    }

    public function evaluations(): HasMany
    {

        return $this->hasMany(Evaluation::class, 'application_id');
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class, 'call_id');
    }

    public function formSchema(): BelongsTo
    {
        return $this->belongsTo(FormSchema::class, 'form_schema_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ApplicationAnswer::class, 'application_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            Document::class,
            'document_has_application',
            'application_id',
            'document_id'
        )
            ->withPivot('type_of_application_id')
            ->withTimestamps();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class, 'application_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class, 'call_id', 'call_id');
    }

    public function mentorships(): HasMany
    {
        return $this->hasMany(Mentorship::class, 'application_id');
    }

    public function kpis(): HasMany
    {
        return $this->hasMany(ProjectKpi::class, 'application_id');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ProjectOutput::class, 'application_id');
    }

    public function getAcademicFlagAttribute(): ?bool
    {

        if (!$this->relationLoaded('team')) {
            return null;
        }

        $members = $this->team?->members;

        if ($members === null || $members->isEmpty()) {
            return null;
        }

        $hasUnknown = false;

        foreach ($members as $member) {
            $student = $member->student;

            if ($student === null) {
                $hasUnknown = true;
                continue;
            }

            if (!$student->relationLoaded('academicFlags') || $student->academicFlags->isEmpty()) {
                return false;
            }
        }

        return $hasUnknown ? null : true;
    }
}
