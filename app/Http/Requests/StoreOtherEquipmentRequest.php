<?php

namespace App\Http\Requests;

use App\Models\OtherEquipment;
use App\Models\OtherEquipmentCatalogItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOtherEquipmentRequest extends FormRequest
{
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
            'other_equipment_catalog_item_id' => [
                'nullable',
                'integer',
                Rule::exists('other_equipment_catalog_items', 'id')
                    ->where(function ($query): void {
                        $query
                            ->where('is_active', true)
                            ->orWhere('id', $this->route('otherEquipment')?->other_equipment_catalog_item_id ?? 0);
                    }),
            ],
            'item_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('other_equipment', 'item_code')->ignore($this->route('otherEquipment')?->id),
            ],
            'item_name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(OtherEquipment::CATEGORIES)],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'specifications' => ['nullable', 'string', 'max:2000'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'property_number' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'qr_code' => ['nullable', 'string', 'max:255'],
            'acquisition_date' => ['nullable', 'date'],
            'acquisition_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'funding_source' => ['nullable', 'string', 'max:255'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'date_delivered' => ['nullable', 'date'],
            'ier_no' => ['nullable', 'string', 'max:255'],
            'warranty_expires_on' => ['nullable', 'date'],
            'useful_life_years' => ['nullable', 'integer', 'min:0', 'max:100'],
            'current_location' => ['nullable', 'string', 'max:255'],
            'assigned_personnel' => ['nullable', 'string', 'max:255'],
            'condition' => ['required', 'string', Rule::in(OtherEquipment::CONDITIONS)],
            'status' => ['required', 'string', Rule::in(OtherEquipment::STATUSES)],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'movement_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * When the equipment is picked from the division catalog, the catalog
     * entry is the source of truth for the item's identity fields.
     */
    protected function prepareForValidation(): void
    {
        $equipment = $this->route('otherEquipment');
        $catalogItem = $this->input('other_equipment_catalog_item_id')
            ? OtherEquipmentCatalogItem::query()
                ->whereKey($this->input('other_equipment_catalog_item_id'))
                ->where(function ($query) use ($equipment): void {
                    $query
                        ->where('is_active', true)
                        ->orWhere('id', $equipment?->other_equipment_catalog_item_id ?? 0);
                })
                ->first()
            : null;

        if (! $catalogItem) {
            return;
        }

        $this->merge([
            'item_name' => $catalogItem->item_name,
            'category' => $catalogItem->category,
            'brand' => $catalogItem->brand,
            'model' => $catalogItem->model,
            'specifications' => $catalogItem->specifications,
            'manufacturer' => $catalogItem->manufacturer,
        ]);
    }
}
