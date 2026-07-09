<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIctEquipmentCatalogItemRequest;
use App\Models\IctEquipment;
use App\Models\IctEquipmentCatalogItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class IctEquipmentCatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $category = $request->string('category')->toString();

        $catalogItems = IctEquipmentCatalogItem::query()
            ->withCount('ictEquipment')
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
            ->through(fn (IctEquipmentCatalogItem $item): array => [
                'id' => $item->id,
                'item_name' => $item->item_name,
                'category' => $item->category,
                'brand' => $item->brand,
                'model' => $item->model,
                'specifications' => $item->specifications,
                'manufacturer' => $item->manufacturer,
                'description' => $item->description,
                'is_active' => $item->is_active,
                'schools_using' => $item->ict_equipment_count,
            ]);

        return Inertia::render('AdminIctEquipmentCatalog', [
            'filters' => [
                'search' => $search,
                'category' => $category !== '' ? $category : null,
            ],
            'categories' => IctEquipment::CATEGORIES,
            'catalogItems' => $catalogItems,
        ]);
    }

    public function store(StoreIctEquipmentCatalogItemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        IctEquipmentCatalogItem::create($data);

        return back()->with('status', 'Equipment added to the catalog.');
    }

    public function update(StoreIctEquipmentCatalogItemRequest $request, IctEquipmentCatalogItem $ictEquipmentCatalogItem): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', $ictEquipmentCatalogItem->is_active);

        $ictEquipmentCatalogItem->update($data);

        return back()->with('status', 'Catalog equipment updated.');
    }

    public function destroy(IctEquipmentCatalogItem $ictEquipmentCatalogItem): RedirectResponse
    {
        if ($ictEquipmentCatalogItem->ictEquipment()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'ict_equipment_catalog_item' => 'This equipment is already used by school records. Deactivate it instead.',
            ]);
        }

        $ictEquipmentCatalogItem->delete();

        return back()->with('status', 'Equipment removed from the catalog.');
    }
}
