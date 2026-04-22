<?php

namespace Modules\Applications\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
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
            'submitted_at' => $this->submitted_at,
            'last_update' => $this->last_update,
            'call' => [
                'id' => $this->call?->id,
                'name' => $this->call?->name,
            ],
            'team_id' => $this->team_id,
            'created_by' => $this->created_by,
            'status' => [
                'id' => $this->status?->id,
                'name' => $this->status?->name,
            ],
            'documents' => $this->documents->map(function ($document) {
                return [
                    'id' => $document->id,
                    'type_of_application_id' => $document->pivot?->type_of_application_id,
                ];
            })->values(),
            'status_history' => $this->whenLoaded('statusHistory', function () {
                return $this->statusHistory
                    ->map(function ($history) {
                        return [
                            'id' => $history->id,
                            'status' => [
                                'id' => $history->status?->id,
                                'name' => $history->status?->name,
                            ],
                            'note' => $history->note,
                            'created_at' => $history->created_at,
                        ];
                    })
                    ->values();
            }),
        ];
    }
}
