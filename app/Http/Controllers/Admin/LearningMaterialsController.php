<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LearningMaterialsController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $materials = LearningResource::query()
            ->with(['school:id,school_id,school_name', 'learningResourceType:id,name,category'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->whereHas('learningResourceType', function ($typeQuery) use ($search): void {
                            $typeQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('publisher', 'like', "%{$search}%")
                        ->orWhereHas('school', function ($schoolQuery) use ($search): void {
                            $schoolQuery
                                ->where('school_name', 'like', "%{$search}%")
                                ->orWhere('school_id', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (LearningResource $resource): array => [
                'id' => $resource->id,
                'resource_type' => $resource->learningResourceType?->name,
                'category' => $resource->learningResourceType?->category,
                'title' => $resource->title,
                'publisher' => $resource->publisher,
                'quantity_delivered' => $resource->quantity_delivered,
                'quantity_with_issue_defect' => $resource->quantity_with_issue_defect,
                'remarks' => $resource->remarks,
                'school_id' => $resource->school?->school_id,
                'school_name' => $resource->school?->school_name,
            ]);

        return Inertia::render('AdminLearningMaterials', [
            'filters' => [
                'search' => $search,
            ],
            'materials' => $materials,
        ]);
    }
}
