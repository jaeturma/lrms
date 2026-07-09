<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResourceDistributionRequest;
use App\Models\ResourceDistribution;
use App\Models\ResourceTitle;
use App\Models\School;
use App\Services\ResourceDistributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DistributionController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $distributions = ResourceDistribution::query()
            ->with(['school:id,school_id,school_name', 'learningResourceType:id,name', 'resourceTitle:id,title,author', 'creator:id,name', 'receiver:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('reference_code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhereHas('resourceTitle', function ($titleQuery) use ($search): void {
                            $titleQuery->where('author', 'like', "%{$search}%");
                        })
                        ->orWhereHas('school', function ($schoolQuery) use ($search): void {
                            $schoolQuery
                                ->where('school_name', 'like', "%{$search}%")
                                ->orWhere('school_id', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ResourceDistribution $distribution): array => [
                'id' => $distribution->id,
                'reference_code' => $distribution->reference_code,
                'school_name' => $distribution->school?->school_name,
                'school_code' => $distribution->school?->school_id,
                'resource_type' => $distribution->learningResourceType?->name,
                'resource_title_id' => $distribution->resource_title_id,
                'title' => $distribution->title,
                'author' => $distribution->resourceTitle?->author,
                'publisher' => $distribution->publisher,
                'quantity' => $distribution->quantity,
                'quantity_damaged' => $distribution->quantity_damaged,
                'status' => $distribution->status,
                'notes' => $distribution->notes,
                'created_by' => $distribution->creator?->name,
                'received_by' => $distribution->receiver?->name,
                'received_at' => $distribution->received_at?->toIso8601String(),
                'created_at' => $distribution->created_at?->toIso8601String(),
            ]);

        return Inertia::render('AdminDistributions', [
            'filters' => [
                'search' => $search,
                'status' => $status !== '' ? $status : null,
            ],
            'statuses' => ResourceDistribution::STATUSES,
            'distributions' => $distributions,
            'schools' => School::query()->orderBy('school_name')->get(['id', 'school_name']),
            'resourceTitles' => ResourceTitle::query()
                ->with(['learningResourceType:id,name', 'gradeLevel:id,name'])
                ->where('is_active', true)
                ->orderBy('title')
                ->get()
                ->map(fn (ResourceTitle $title): array => [
                    'id' => $title->id,
                    'title' => $title->title,
                    'author' => $title->author,
                    'publisher' => $title->publisher,
                    'resource_type' => $title->learningResourceType?->name,
                    'grade_level' => $title->gradeLevel?->name,
                ]),
            'summary' => ResourceDistribution::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
        ]);
    }

    public function store(
        StoreResourceDistributionRequest $request,
        ResourceDistributionService $distributionService,
    ): RedirectResponse {
        $distribution = $distributionService->create($request->validated(), $request->user());

        return back()->with('status', "Delivery {$distribution->reference_code} recorded.");
    }

    public function cancel(
        ResourceDistribution $distribution,
        ResourceDistributionService $distributionService,
    ): RedirectResponse {
        $distributionService->cancel($distribution);

        return back()->with('status', "Delivery {$distribution->reference_code} cancelled.");
    }
}
