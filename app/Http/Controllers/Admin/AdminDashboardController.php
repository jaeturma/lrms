<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\LearningResource;
use App\Models\LearningResourceType;
use App\Models\School;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $districtId = $request->integer('district_id');

        $reportsByDistrict = District::query()
            ->withCount('schools')
            ->orderBy('name')
            ->get()
            ->map(fn (District $district): array => [
                'district' => $district->name,
                'school_count' => $district->schools_count,
            ]);

        $schools = School::query()
            ->with(['district', 'municipality'])
            ->withCount('learningResources')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('school_name', 'like', "%{$search}%")
                        ->orWhere('school_id', 'like', "%{$search}%");
                });
            })
            ->when($districtId > 0, fn ($query) => $query->where('district_id', $districtId))
            ->orderBy('school_name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (School $school): array => [
                'school_id' => $school->school_id,
                'school_name' => $school->school_name,
                'district' => $school->district?->name,
                'municipality' => $school->municipality?->name,
                'is_activated' => $school->is_activated,
                'learning_resources_count' => $school->learning_resources_count,
            ]);

        return Inertia::render('AdminDashboard', [
            'stats' => [
                'total_schools' => School::count(),
                'activated_schools' => School::where('is_activated', true)->count(),
                'pending_schools' => School::where('is_activated', false)->count(),
                'total_learning_resources' => LearningResource::count(),
            ],
            'districts' => District::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'district_id' => $districtId > 0 ? $districtId : null,
            ],
            'reportsByDistrict' => $reportsByDistrict,
            'schools' => $schools,
            'learningResourceTypes' => LearningResourceType::query()
                ->orderBy('name')
                ->get(['id', 'name', 'is_active']),
        ]);
    }
}
