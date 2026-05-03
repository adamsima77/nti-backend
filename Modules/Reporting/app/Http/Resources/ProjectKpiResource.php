<?php

namespace Modules\Reporting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectKpiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'metric_name' => $this->metric_name,
            'target_value' => $this->target_value,
            'actual_value' => $this->actual_value,
            'unit' => $this->unit,
            'description' => $this->description,
            'achievement_percentage' => $this->achievement_percentage,
            'target_met' => $this->isTargetMet(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
