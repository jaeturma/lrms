<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearningResourceResource extends JsonResource
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
            'learning_resource_type_id' => $this->learning_resource_type_id,
            'resource_title_id' => $this->resource_title_id,
            'resource_type' => $this->learningResourceType?->name,
            'title' => $this->title,
            'quantity_delivered' => $this->quantity_delivered,
            'quantity_with_issue_defect' => $this->quantity_with_issue_defect,
            'remarks' => $this->remarks,
            'publisher' => $this->publisher,
            'inventory' => $this->whenLoaded('inventory', fn (): array => [
                'available' => $this->inventory->available,
                'issued' => $this->inventory->issued,
                'borrowed' => $this->inventory->borrowed,
                'damaged' => $this->inventory->damaged,
                'lost' => $this->inventory->lost,
                'condemned' => $this->inventory->condemned,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
