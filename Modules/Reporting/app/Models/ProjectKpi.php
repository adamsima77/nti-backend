<?php

namespace Modules\Reporting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Applications\Models\Application;

class ProjectKpi extends Model
{
    use HasFactory;

    protected $table = 'project_kpi';

    protected $fillable = [
        'application_id',
        'metric_name',
        'target_value',
        'actual_value',
        'unit',
        'description',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'actual_value' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Application that this KPI belongs to
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * Calculate achievement percentage
     */
    public function getAchievementPercentageAttribute(): ?float
    {
        if ($this->target_value === null || $this->target_value == 0 || $this->actual_value === null) {
            return null;
        }

        return round(($this->actual_value / $this->target_value) * 100, 2);
    }

    /**
     * Check if KPI target was met
     */
    public function isTargetMet(): bool
    {
        if ($this->target_value === null || $this->actual_value === null) {
            return false;
        }

        return $this->actual_value >= $this->target_value;
    }
}
