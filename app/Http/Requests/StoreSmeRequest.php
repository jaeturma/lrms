<?php

namespace App\Http\Requests;

use App\Models\Sme;
use App\Models\SmeCatalogItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSmeRequest extends FormRequest
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
            'sme_catalog_item_id' => [
                'nullable',
                'integer',
                Rule::exists('sme_catalog_items', 'id')
                    ->where(function ($query): void {
                        $query
                            ->where('is_active', true)
                            ->orWhere('id', $this->route('sme')?->sme_catalog_item_id ?? 0);
                    }),
            ],
            'item_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('sme', 'item_code')->ignore($this->route('sme')?->id),
            ],
            'item_name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(Sme::CATEGORIES)],
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
            'condition' => ['required', 'string', Rule::in(Sme::CONDITIONS)],
            'status' => ['required', 'string', Rule::in(Sme::STATUSES)],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'movement_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * When the SME item is picked from the division catalog, the catalog
     * entry is the source of truth for the item's identity fields.
     */
    protected function prepareForValidation(): void
    {
        $sme = $this->route('sme');
        $catalogItem = $this->input('sme_catalog_item_id')
            ? SmeCatalogItem::query()
                ->whereKey($this->input('sme_catalog_item_id'))
                ->where(function ($query) use ($sme): void {
                    $query
                        ->where('is_active', true)
                        ->orWhere('id', $sme?->sme_catalog_item_id ?? 0);
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
