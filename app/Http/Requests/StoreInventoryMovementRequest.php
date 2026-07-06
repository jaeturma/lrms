<?php

namespace App\Http\Requests;

use App\Models\LearningResourceInventory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryMovementRequest extends FormRequest
{
    public const MOVEMENT_TYPES = [
        'issued',
        'borrowed',
        'returned',
        'damaged',
        'lost',
        'condemned',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(self::MOVEMENT_TYPES)],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'from_status' => ['nullable', 'string', Rule::in(LearningResourceInventory::STATUSES)],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
