<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOtherEquipmentCatalogItemRequest;
use App\Models\OtherEquipment;
use App\Models\OtherEquipmentCatalogItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OtherEquipmentCatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $category = $request->string('category')->toString();

        $catalogItems = OtherEquipmentCatalogItem::query()
            ->withCount('otherEquipment')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('item_name', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('manufacturer', 'like', "%{$search}%");
                });
            })
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->orderBy('item_name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (OtherEquipmentCatalogItem $item): array => [
                'id' => $item->id,
                'item_name' => $item->item_name,
                'category' => $item->category,
                'brand' => $item->brand,
                'model' => $item->model,
                'specifications' => $item->specifications,
                'manufacturer' => $item->manufacturer,
                'description' => $item->description,
                'is_active' => $item->is_active,
                'schools_using' => $item->other_equipment_count,
            ]);

        return Inertia::render('AdminOtherEquipmentCatalog', [
            'filters' => [
                'search' => $search,
                'category' => $category !== '' ? $category : null,
            ],
            'categories' => OtherEquipment::CATEGORIES,
            'catalogItems' => $catalogItems,
        ]);
    }

    public function store(StoreOtherEquipmentCatalogItemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        OtherEquipmentCatalogItem::create($data);

        return back()->with('status', 'Equipment added to the catalog.');
    }

    public function update(StoreOtherEquipmentCatalogItemRequest $request, OtherEquipmentCatalogItem $otherEquipmentCatalogItem): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', $otherEquipmentCatalogItem->is_active);

        $otherEquipmentCatalogItem->update($data);

        return back()->with('status', 'Catalog equipment updated.');
    }

    public function destroy(OtherEquipmentCatalogItem $otherEquipmentCatalogItem): RedirectResponse
    {
        if ($otherEquipmentCatalogItem->otherEquipment()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'other_equipment_catalog_item' => 'This equipment is already used by school records. Deactivate it instead.',
            ]);
        }

        $otherEquipmentCatalogItem->delete();

        return back()->with('status', 'Equipment removed from the catalog.');
    }
}
