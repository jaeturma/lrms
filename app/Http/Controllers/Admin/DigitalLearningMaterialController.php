<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDigitalLearningMaterialRequest;
use App\Models\DigitalLearningMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DigitalLearningMaterialController extends Controller
{
    private const MANAGE_ROLES = ['admin', 'superadmin', 'sysadmin', 'ito', 'manager', 'librarian', 'supply'];

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $category = $request->string('category')->toString();
        $type = $request->string('type')->toString();
        $qualityAssured = $request->string('quality_assured')->toString();

        $materials = DigitalLearningMaterial::query()
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
            ->when($qualityAssured !== '', fn ($query) => $query->where('quality_assured', $qualityAssured === '1'))
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
                'is_active' => $material->is_active,
            ]);

        return Inertia::render('AdminDigitalLearningMaterials', [
            'filters' => [
                'search' => $search,
                'category' => $category !== '' ? $category : null,
                'type' => $type !== '' ? $type : null,
                'quality_assured' => $qualityAssured !== '' ? $qualityAssured : null,
            ],
            'categories' => DigitalLearningMaterial::CATEGORIES,
            'types' => DigitalLearningMaterial::TYPES,
            'materials' => $materials,
            'canManage' => in_array($request->user()?->role, self::MANAGE_ROLES, true),
        ]);
    }

    public function store(StoreDigitalLearningMaterialRequest $request): RedirectResponse
    {
        $data = $this->dataWithUploads($request);

        DigitalLearningMaterial::create($data);

        return back()->with('status', 'Digital learning material added to the catalog.');
    }

    public function update(StoreDigitalLearningMaterialRequest $request, DigitalLearningMaterial $digitalLearningMaterial): RedirectResponse
    {
        $data = $this->dataWithUploads($request, $digitalLearningMaterial);

        $digitalLearningMaterial->update($data);

        return back()->with('status', 'Digital learning material updated.');
    }

    public function destroy(DigitalLearningMaterial $digitalLearningMaterial): RedirectResponse
    {
        $this->deleteFile($digitalLearningMaterial->cover_image_path);
        $this->deleteFile($digitalLearningMaterial->attachment_path);

        $digitalLearningMaterial->delete();

        return back()->with('status', 'Digital learning material removed from the catalog.');
    }

    /**
     * Merge validated fields with stored upload paths, replacing (and
     * cleaning up) previous files when new ones are provided.
     *
     * @return array<string, mixed>
     */
    private function dataWithUploads(StoreDigitalLearningMaterialRequest $request, ?DigitalLearningMaterial $existing = null): array
    {
        $data = $request->safe()->except(['cover_image', 'attachment']);
        $data['quality_assured'] = $request->boolean('quality_assured', $existing?->quality_assured ?? false);
        $data['is_active'] = $request->boolean('is_active', $existing?->is_active ?? true);

        if ($request->hasFile('cover_image')) {
            $this->deleteFile($existing?->cover_image_path);
            $data['cover_image_path'] = $request->file('cover_image')->store('digital-learning-materials/covers', 'public');
        }

        if ($request->hasFile('attachment')) {
            $this->deleteFile($existing?->attachment_path);
            $data['attachment_path'] = $request->file('attachment')->store('digital-learning-materials/attachments', 'public');
        }

        return $data;
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
