<?php

namespace App\Http\Controllers;

use App\Models\DigitalLearningMaterial;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolDigitalLearningMaterialController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $category = $request->string('category')->toString();
        $type = $request->string('type')->toString();

        $materials = DigitalLearningMaterial::query()
            ->where('is_active', true)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('publisher', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (DigitalLearningMaterial $material): array => [
                'id' => $material->id,
                'name' => $material->name,
                'category' => $material->category,
                'type' => $material->type,
                'publisher' => $material->publisher,
                'link' => $material->link,
                'description' => $material->description,
                'cover_image_url' => $material->coverImageUrl(),
                'attachment_url' => $material->attachmentUrl(),
                'quality_assured' => $material->quality_assured,
            ]);

        return Inertia::render('SchoolDigitalLearningMaterials', [
            'filters' => [
                'search' => $search,
                'category' => $category !== '' ? $category : null,
                'type' => $type !== '' ? $type : null,
            ],
            'categories' => DigitalLearningMaterial::CATEGORIES,
            'types' => DigitalLearningMaterial::TYPES,
            'materials' => $materials,
        ]);
    }
}
