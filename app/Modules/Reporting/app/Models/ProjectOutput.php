<?php

namespace Modules\Reporting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Applications\Models\Application;
use Modules\Applications\Models\Document;

class ProjectOutput extends Model
{
    use HasFactory;

    protected $table = 'project_output';

    protected $fillable = [
        'application_id',
        'output_name',
        'description',
        'output_type',
        'status',
        'planned_delivery',
        'actual_delivery',
    ];

    protected $casts = [
        'planned_delivery' => 'datetime',
        'actual_delivery' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Application that this output belongs to
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    /**
     * Documents attached to this output
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            Document::class,
            'document_has_project_output',
            'project_output_id',
            'document_id'
        )
            ->withTimestamps();
    }

    /**
     * Check if output is overdue
     */
    public function isOverdue(): bool
    {
        if ($this->planned_delivery === null || $this->status === 'completed') {
            return false;
        }

        return now()->isAfter($this->planned_delivery);
    }

    /**
     * Check if output is on time
     */
    public function isOnTime(): bool
    {
        if ($this->actual_delivery === null || $this->planned_delivery === null) {
            return true;
        }

        return $this->actual_delivery->lte($this->planned_delivery);
    }

    /**
     * Mark output as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'completed',
            'actual_delivery' => now(),
        ]);
    }

    /**
     * Get delivery status label
     */
    public function getDeliveryStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'completed' => 'Completed',
            'delivered' => 'Delivered',
            default => 'Unknown',
        };
    }
}
