<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResourceTitleRequest;
use App\Models\GradeLevel;
use App\Models\LearningResourceType;
use App\Models\ResourceTitle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ResourceTitleController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $typeId = $request->integer('learning_resource_type_id');

        $resourceTitles = ResourceTitle::query()
            ->with(['learningResourceType:id,name', 'gradeLevel:id,name'])
            ->withCount('learningResources')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('author', 'like', "%{$search}%")
                        ->orWhere('publisher', 'like', "%{$search}%")
                        ->orWhere('isbn', 'like', "%{$search}%");
                });
            })
            ->when($typeId > 0, fn ($query) => $query->where('learning_resource_type_id', $typeId))
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ResourceTitle $resourceTitle): array => [
                'id' => $resourceTitle->id,
                'title' => $resourceTitle->title,
                'author' => $resourceTitle->author,
                'publisher' => $resourceTitle->publisher,
                'language' => $resourceTitle->language,
                'subject' => $resourceTitle->subject,
                'volume' => $resourceTitle->volume,
                'edition' => $resourceTitle->edition,
                'copyright_year' => $resourceTitle->copyright_year,
                'pages' => $resourceTitle->pages,
                'isbn' => $resourceTitle->isbn,
                'description' => $resourceTitle->description,
                'media_url' => $resourceTitle->media_url,
                'cover_image_url' => $resourceTitle->coverImageUrl(),
                'attachment_url' => $resourceTitle->attachmentUrl(),
                'is_active' => $resourceTitle->is_active,
                'learning_resource_type_id' => $resourceTitle->learning_resource_type_id,
                'resource_type' => $resourceTitle->learningResourceType?->name,
                'grade_level_id' => $resourceTitle->grade_level_id,
                'grade_level' => $resourceTitle->gradeLevel?->name,
                'schools_using' => $resourceTitle->learning_resources_count,
            ]);

        return Inertia::render('AdminResourceTitles', [
            'filters' => [
                'search' => $search,
                'learning_resource_type_id' => $typeId > 0 ? $typeId : null,
            ],
            'resourceTitles' => $resourceTitles,
            'resourceTypes' => LearningResourceType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'gradeLevels' => GradeLevel::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    public function store(StoreResourceTitleRequest $request): RedirectResponse
    {
        $data = $this->dataWithUploads($request);

        ResourceTitle::create($data);

        return back()->with('status', 'Resource title added to the catalog.');
    }

    public function update(StoreResourceTitleRequest $request, ResourceTitle $resourceTitle): RedirectResponse
    {
        $data = $this->dataWithUploads($request, $resourceTitle);

        $resourceTitle->update($data);

        return back()->with('status', 'Resource title updated.');
    }

    public function destroy(ResourceTitle $resourceTitle): RedirectResponse
    {
        if ($resourceTitle->learningResources()->exists()) {
            throw ValidationException::withMessages([
                'resource_title' => 'This title is already used by school records. Deactivate it instead.',
            ]);
        }

        $this->deleteFile($resourceTitle->cover_image_path);
        $this->deleteFile($resourceTitle->attachment_path);

        $resourceTitle->delete();

        return back()->with('status', 'Resource title removed from the catalog.');
    }

    /**
     * Merge validated fields with stored upload paths, replacing (and
     * cleaning up) previous files when new ones are provided.
     *
     * @return array<string, mixed>
     */
    private function dataWithUploads(StoreResourceTitleRequest $request, ?ResourceTitle $existing = null): array
    {
        $data = $request->safe()->except(['cover_image', 'attachment']);
        $data['is_active'] = $request->boolean('is_active', $existing?->is_active ?? true);

        if ($request->hasFile('cover_image')) {
            $this->deleteFile($existing?->cover_image_path);
            $data['cover_image_path'] = $request->file('cover_image')->store('resource-titles/covers', 'public');
        }

        if ($request->hasFile('attachment')) {
            $this->deleteFile($existing?->attachment_path);
            $data['attachment_path'] = $request->file('attachment')->store('resource-titles/attachments', 'public');
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
