<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStoreSchoolRequest;
use App\Http\Requests\AdminUpdateSchoolRequest;
use App\Http\Resources\SchoolResource;
use App\Models\Barangay;
use App\Models\District;
use App\Models\Municipality;
use App\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SchoolManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $districtId = $request->integer('district_id');

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

        return Inertia::render('AdminSchoolsIndex', [
            'districts' => District::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'district_id' => $districtId > 0 ? $districtId : null,
            ],
            'schools' => $schools,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('AdminSchoolCreate', [
            'districts' => District::query()->orderBy('name')->get(['id', 'name']),
            'municipalities' => Municipality::query()->orderBy('name')->get(['id', 'name', 'district_id']),
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name', 'municipality_id']),
        ]);
    }

    public function store(AdminStoreSchoolRequest $request): RedirectResponse
    {
        $school = School::query()->create($request->validated());

        return redirect()
            ->route('admin.schools.edit', $school)
            ->with('status', 'School created successfully.');
    }

    public function edit(School $school): Response
    {
        return Inertia::render('AdminSchoolEdit', [
            'school' => SchoolResource::make($school->load(['district', 'municipality', 'barangay'])),
            'districts' => District::query()->orderBy('name')->get(['id', 'name']),
            'municipalities' => Municipality::query()->orderBy('name')->get(['id', 'name', 'district_id']),
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name', 'municipality_id']),
        ]);
    }

    public function update(AdminUpdateSchoolRequest $request, School $school): RedirectResponse
    {
        $validated = $request->validated();

        $school->update($validated);

        if ($school->user && ! empty($validated['email'])) {
            $school->user->update([
                'name' => $validated['school_name'],
                'email' => $validated['email'],
            ]);
        }

        return redirect()
            ->route('admin.schools.edit', $school)
            ->with('status', 'School details updated.');
    }

    public function destroy(School $school): RedirectResponse
    {
        DB::transaction(function () use ($school): void {
            if ($school->user) {
                $school->user->delete();
            }

            $school->delete();
        });

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'School deleted successfully.');
    }
}
