<?php

namespace Modules\Mentorship\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Applications\Models\Application;

class Milestone extends Model
{
    use HasFactory;

    protected $table = 'project_milestones';

    protected $fillable = [
        'name',
        'deadline',
        'status',
        'comments',
        'project_id',
    ];

    protected $casts = [
        'deadline' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'project_id');
    }

    public function application(): BelongsTo
    {
        return $this->project();
    }
}
