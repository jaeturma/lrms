<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOtherEquipmentRequest;
use App\Models\OtherEquipment;
use App\Models\OtherEquipmentCatalogItem;
use App\Models\OtherEquipmentMovement;
use App\Models\School;
use App\Services\OtherEquipmentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SchoolOtherEquipmentController extends Controller
{
    public function index(): Response
    {
        $school = $this->resolveSchool();

        $equipment = $school->otherEquipment()
            ->orderBy('item_name')
            ->get()
            ->map(fn (OtherEquipment $item): array => $this->transformEquipment($item));

        $movements = OtherEquipmentMovement::query()
            ->where('school_id', $school->id)
            ->with(['otherEquipment' => fn ($query) => $query->withTrashed()->select('id', 'item_code', 'item_name'), 'user:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (OtherEquipmentMovement $movement): array => [
                'id' => $movement->id,
                'equipment_id' => $movement->other_equipment_id,
                'item_name' => $movement->otherEquipment?->item_name,
                'item_code' => $movement->otherEquipment?->item_code,
                'type' => $movement->type,
                'from_value' => $movement->from_value,
                'to_value' => $movement->to_value,
                'notes' => $movement->notes,
                'recorded_by' => $movement->user?->name,
                'created_at' => $movement->created_at?->toIso8601String(),
            ]);

        $catalog = OtherEquipmentCatalogItem::query()
            ->where('is_active', true)
            ->orderBy('item_name')
            ->get()
            ->map(fn (OtherEquipmentCatalogItem $item): array => [
                'id' => $item->id,
                'item_name' => $item->item_name,
                'category' => $item->category,
                'brand' => $item->brand,
                'model' => $item->model,
                'specifications' => $item->specifications,
                'manufacturer' => $item->manufacturer,
                'description' => $item->description,
            ]);

        return Inertia::render('SchoolOtherEquipment', [
            'equipment' => $equipment,
            'movements' => $movements,
            'catalog' => $catalog,
            'categories' => OtherEquipment::CATEGORIES,
            'conditions' => OtherEquipment::CONDITIONS,
            'statuses' => OtherEquipment::STATUSES,
        ]);
    }

    public function store(StoreOtherEquipmentRequest $request, OtherEquipmentService $equipmentService): RedirectResponse
    {
        $school = $this->resolveSchool();

        $equipmentService->create($school, $request->validated(), $request->user());

        return back()->with('status', 'Equipment registered.');
    }

    public function update(
        StoreOtherEquipmentRequest $request,
        OtherEquipment $otherEquipment,
        OtherEquipmentService $equipmentService,
    ): RedirectResponse {
        $school = $this->resolveSchool();

        abort_if($otherEquipment->school_id !== $school->id, 403);

        $equipmentService->update($otherEquipment, $request->validated(), $request->user());

        return back()->with('status', 'Equipment updated.');
    }

    public function destroy(OtherEquipment $otherEquipment, OtherEquipmentService $equipmentService): RedirectResponse
    {
        $school = $this->resolveSchool();

        abort_if($otherEquipment->school_id !== $school->id, 403);

        $equipmentService->delete($otherEquipment, auth()->user());

        return back()->with('status', 'Equipment removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function transformEquipment(OtherEquipment $item): array
    {
        return [
            'id' => $item->id,
            'other_equipment_catalog_item_id' => $item->other_equipment_catalog_item_id,
            'item_code' => $item->item_code,
            'item_name' => $item->item_name,
            'category' => $item->category,
            'brand' => $item->brand,
            'model' => $item->model,
            'specifications' => $item->specifications,
            'manufacturer' => $item->manufacturer,
            'serial_number' => $item->serial_number,
            'property_number' => $item->property_number,
            'barcode' => $item->barcode,
            'qr_code' => $item->qr_code,
            'acquisition_date' => $item->acquisition_date?->toDateString(),
            'acquisition_cost' => $item->acquisition_cost,
            'funding_source' => $item->funding_source,
            'supplier' => $item->supplier,
            'date_delivered' => $item->date_delivered?->toDateString(),
            'ier_no' => $item->ier_no,
            'warranty_expires_on' => $item->warranty_expires_on?->toDateString(),
            'useful_life_years' => $item->useful_life_years,
            'current_location' => $item->current_location,
            'assigned_personnel' => $item->assigned_personnel,
            'condition' => $item->condition,
            'status' => $item->status,
            'remarks' => $item->remarks,
        ];
    }

    private function resolveSchool(): School
    {
        $school = auth()->user()?->school;

        abort_if(! $school, 403);

        return $school;
    }
}
