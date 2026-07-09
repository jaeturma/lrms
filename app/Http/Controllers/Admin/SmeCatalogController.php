<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSmeCatalogItemRequest;
use App\Models\Sme;
use App\Models\SmeCatalogItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SmeCatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $category = $request->string('category')->toString();

        $catalogItems = SmeCatalogItem::query()
            ->withCount('sme')
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
            ->through(fn (SmeCatalogItem $item): array => [
                'id' => $item->id,
                'item_name' => $item->item_name,
                'category' => $item->category,
                'brand' => $item->brand,
                'model' => $item->model,
                'specifications' => $item->specifications,
                'manufacturer' => $item->manufacturer,
                'description' => $item->description,
                'is_active' => $item->is_active,
                'schools_using' => $item->sme_count,
            ]);

        return Inertia::render('AdminSmeCatalog', [
            'filters' => [
                'search' => $search,
                'category' => $category !== '' ? $category : null,
            ],
            'categories' => Sme::CATEGORIES,
            'catalogItems' => $catalogItems,
        ]);
    }

    public function store(StoreSmeCatalogItemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        SmeCatalogItem::create($data);

        return back()->with('status', 'SME item added to the catalog.');
    }

    public function update(StoreSmeCatalogItemRequest $request, SmeCatalogItem $smeCatalogItem): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', $smeCatalogItem->is_active);

        $smeCatalogItem->update($data);

        return back()->with('status', 'Catalog SME item updated.');
    }

    public function destroy(SmeCatalogItem $smeCatalogItem): RedirectResponse
    {
        if ($smeCatalogItem->sme()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'sme_catalog_item' => 'This SME item is already used by school records. Deactivate it instead.',
            ]);
        }

        $smeCatalogItem->delete();

        return back()->with('status', 'SME item removed from the catalog.');
    }
}
