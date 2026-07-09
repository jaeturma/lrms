<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSmeRequest;
use App\Models\School;
use App\Models\Sme;
use App\Models\SmeCatalogItem;
use App\Models\SmeMovement;
use App\Services\SmeService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SchoolSmeController extends Controller
{
    public function index(): Response
    {
        $school = $this->resolveSchool();

        $sme = $school->sme()
            ->orderBy('item_name')
            ->get()
            ->map(fn (Sme $item): array => $this->transformSme($item));

        $movements = SmeMovement::query()
            ->where('school_id', $school->id)
            ->with(['sme' => fn ($query) => $query->withTrashed()->select('id', 'item_code', 'item_name'), 'user:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (SmeMovement $movement): array => [
                'id' => $movement->id,
                'sme_id' => $movement->sme_id,
                'item_name' => $movement->sme?->item_name,
                'item_code' => $movement->sme?->item_code,
                'type' => $movement->type,
                'from_value' => $movement->from_value,
                'to_value' => $movement->to_value,
                'notes' => $movement->notes,
                'recorded_by' => $movement->user?->name,
                'created_at' => $movement->created_at?->toIso8601String(),
            ]);

        $catalog = SmeCatalogItem::query()
            ->where('is_active', true)
            ->orderBy('item_name')
            ->get()
            ->map(fn (SmeCatalogItem $item): array => [
                'id' => $item->id,
                'item_name' => $item->item_name,
                'category' => $item->category,
                'brand' => $item->brand,
                'model' => $item->model,
                'specifications' => $item->specifications,
                'manufacturer' => $item->manufacturer,
                'description' => $item->description,
            ]);

        return Inertia::render('SchoolSme', [
            'sme' => $sme,
            'movements' => $movements,
            'catalog' => $catalog,
            'categories' => Sme::CATEGORIES,
            'conditions' => Sme::CONDITIONS,
            'statuses' => Sme::STATUSES,
        ]);
    }

    public function store(StoreSmeRequest $request, SmeService $smeService): RedirectResponse
    {
        $school = $this->resolveSchool();

        $smeService->create($school, $request->validated(), $request->user());

        return back()->with('status', 'SME item registered.');
    }

    public function update(
        StoreSmeRequest $request,
        Sme $sme,
        SmeService $smeService,
    ): RedirectResponse {
        $school = $this->resolveSchool();

        abort_if($sme->school_id !== $school->id, 403);

        $smeService->update($sme, $request->validated(), $request->user());

        return back()->with('status', 'SME item updated.');
    }

    public function destroy(Sme $sme, SmeService $smeService): RedirectResponse
    {
        $school = $this->resolveSchool();

        abort_if($sme->school_id !== $school->id, 403);

        $smeService->delete($sme, auth()->user());

        return back()->with('status', 'SME item removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function transformSme(Sme $item): array
    {
        return [
            'id' => $item->id,
            'sme_catalog_item_id' => $item->sme_catalog_item_id,
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
