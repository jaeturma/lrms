<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Enrollment;
use App\Models\Equipment;
use App\Models\LearningResource;
use App\Models\School;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->with(['district', 'municipality', 'barangay'])
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
                'barangay' => $school->barangay?->name,
                'is_activated' => $school->is_activated,
                'learning_resources_count' => $school->learning_resources_count,
            ]);

        $activeSchoolYear = SchoolYear::active();

        return Inertia::render('AdminDashboard', [
            'stats' => [
                'total_schools' => School::count(),
                'activated_schools' => School::where('is_activated', true)->count(),
                'pending_schools' => School::where('is_activated', false)->count(),
                'total_learning_resources' => LearningResource::count(),
                'total_equipment' => Equipment::count(),
                'total_learners' => $activeSchoolYear
                    ? (int) Enrollment::query()
                        ->where('school_year_id', $activeSchoolYear->id)
                        ->sum(DB::raw('male_count + female_count'))
                    : 0,
            ],
            'activeSchoolYear' => $activeSchoolYear?->only(['id', 'name']),
            'districts' => District::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'district_id' => $districtId > 0 ? $districtId : null,
            ],
            'reportsByDistrict' => $reportsByDistrict,
            'schools' => $schools,
        ]);
    }
}
