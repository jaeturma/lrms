<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolResource extends JsonResource
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
            'district_id' => $this->district_id,
            'municipality_id' => $this->municipality_id,
            'barangay_id' => $this->barangay_id,
            'school_id' => $this->school_id,
            'school_name' => $this->school_name,
            'school_head' => $this->school_head,
            'librarian' => $this->librarian,
            'property_custodian' => $this->property_custodian,
            'email' => $this->email,
            'is_activated' => $this->is_activated,
            'district' => $this->whenLoaded('district', fn () => $this->district?->name),
            'municipality' => $this->whenLoaded('municipality', fn () => $this->municipality?->name),
            'barangay' => $this->whenLoaded('barangay', fn () => $this->barangay?->name),
        ];
    }
}
