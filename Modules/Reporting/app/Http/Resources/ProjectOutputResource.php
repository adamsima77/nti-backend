<?php

namespace Modules\Reporting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectOutputResource extends JsonResource
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
            'output_name' => $this->output_name,
            'description' => $this->description,
            'output_type' => $this->output_type,
            'status' => $this->status,
            'status_label' => $this->getDeliveryStatusLabel(),
            'planned_delivery' => $this->planned_delivery,
            'actual_delivery' => $this->actual_delivery,
            'is_overdue' => $this->isOverdue(),
            'is_on_time' => $this->isOnTime(),
            'documents' => $this->whenLoaded('documents', function () {
                return $this->documents->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                    ];
                })->values();
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
