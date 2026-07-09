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
        $user = $request->user();
        $canViewContactDetails = $user !== null
            && ($user->role === 'admin' || (int) $user->school_id === (int) $this->id);

        return [
            'id' => $this->id,
            'district_id' => $this->district_id,
            'municipality_id' => $this->municipality_id,
            'barangay_id' => $this->barangay_id,
            'school_id' => $this->school_id,
            'school_name' => $this->school_name,
            'school_type' => $this->school_type,
            'school_head' => $this->when($canViewContactDetails, $this->school_head),
            'librarian' => $this->when($canViewContactDetails, $this->librarian),
            'property_custodian' => $this->when($canViewContactDetails, $this->property_custodian),
            'primary_mobile_no' => $this->when($canViewContactDetails, $this->primary_mobile_no),
            'secondary_mobile_no' => $this->when($canViewContactDetails, $this->secondary_mobile_no),
            'email' => $this->when($canViewContactDetails, $this->email),
            'is_activated' => $this->is_activated,
            'is_profile_complete' => filled($this->school_head) && filled($this->email),
            'activation_requested_at' => $this->activation_requested_at?->toIso8601String(),
            'district' => $this->whenLoaded('district', fn () => $this->district?->name),
            'municipality' => $this->whenLoaded('municipality', fn () => $this->municipality?->name),
            'barangay' => $this->whenLoaded('barangay', fn () => $this->barangay?->name),
        ];
    }
}
