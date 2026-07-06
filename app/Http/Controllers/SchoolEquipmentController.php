<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipmentRequest;
use App\Models\Equipment;
use App\Models\EquipmentMovement;
use App\Models\School;
use App\Services\EquipmentService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SchoolEquipmentController extends Controller
{
    public function index(): Response
    {
        $school = $this->resolveSchool();

        $equipment = $school->equipment()
            ->orderBy('item_name')
            ->get()
            ->map(fn (Equipment $item): array => $this->transformEquipment($item));

        $movements = EquipmentMovement::query()
            ->where('school_id', $school->id)
            ->with(['equipment' => fn ($query) => $query->withTrashed()->select('id', 'item_code', 'item_name'), 'user:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (EquipmentMovement $movement): array => [
                'id' => $movement->id,
                'equipment_id' => $movement->equipment_id,
                'item_name' => $movement->equipment?->item_name,
                'item_code' => $movement->equipment?->item_code,
                'type' => $movement->type,
                'from_value' => $movement->from_value,
                'to_value' => $movement->to_value,
                'notes' => $movement->notes,
                'recorded_by' => $movement->user?->name,
                'created_at' => $movement->created_at?->toIso8601String(),
            ]);

        return Inertia::render('SchoolEquipment', [
            'equipment' => $equipment,
            'movements' => $movements,
            'categories' => Equipment::CATEGORIES,
            'conditions' => Equipment::CONDITIONS,
            'statuses' => Equipment::STATUSES,
        ]);
    }

    public function store(StoreEquipmentRequest $request, EquipmentService $equipmentService): RedirectResponse
    {
        $school = $this->resolveSchool();

        $equipmentService->create($school, $request->validated(), $request->user());

        return back()->with('status', 'Equipment registered.');
    }

    public function update(
        StoreEquipmentRequest $request,
        Equipment $equipment,
        EquipmentService $equipmentService,
    ): RedirectResponse {
        $school = $this->resolveSchool();

        abort_if($equipment->school_id !== $school->id, 403);

        $equipmentService->update($equipment, $request->validated(), $request->user());

        return back()->with('status', 'Equipment updated.');
    }

    public function destroy(Equipment $equipment, EquipmentService $equipmentService): RedirectResponse
    {
        $school = $this->resolveSchool();

        abort_if($equipment->school_id !== $school->id, 403);

        $equipmentService->delete($equipment, auth()->user());

        return back()->with('status', 'Equipment removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function transformEquipment(Equipment $item): array
    {
        return [
            'id' => $item->id,
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
