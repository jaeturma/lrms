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
            'resource_type' => $this->resource_type,
            'issue_defect' => $this->issue_defect,
            'quantity' => $this->quantity,
            'publisher' => $this->publisher,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
