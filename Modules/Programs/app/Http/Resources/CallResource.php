<?php

namespace Modules\Programs\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CallResource extends JsonResource
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
            'name' => $this->name,
            'application_deadline' => $this->application_deadline,
            'project_start' => $this->project_start,
            'project_end' => $this->project_end,
            'description' => $this->description,
            'program' => [
                'id' => $this->program?->id,
                'name' => $this->program?->name,
            ],
            'organization' => [
                'id' => $this->organization?->id,
                'name' => $this->organization?->name,
            ],
        ];
    }
}
