<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStoreSchoolRequest;
use App\Http\Requests\AdminUpdateSchoolRequest;
use App\Http\Resources\LearningResourceResource;
use App\Http\Resources\SchoolResource;
use App\Models\Barangay;
use App\Models\District;
use App\Models\Enrollment;
use App\Models\Municipality;
use App\Models\School;
use App\Models\SchoolYear;
use App\Services\SchoolActivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SchoolManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $districtId = $request->integer('district_id');

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
            ->orderByDesc('activation_requested_at')
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
                'activation_requested_at' => $school->activation_requested_at?->toIso8601String(),
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
            'schoolTypes' => School::SCHOOL_TYPES,
            'municipalities' => Municipality::query()->orderBy('name')->get(['id', 'name']),
            'districts' => District::query()->orderBy('name')->get(['id', 'name', 'municipality_id']),
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
        $school->load(['district', 'municipality', 'barangay']);

        return Inertia::render('AdminSchoolEdit', [
            'school' => SchoolResource::make($school)->resolve(),
            'schoolTypes' => School::SCHOOL_TYPES,
            'municipalities' => Municipality::query()->orderBy('name')->get(['id', 'name']),
            'districts' => District::query()->orderBy('name')->get(['id', 'name', 'municipality_id']),
            'barangays' => Barangay::query()->orderBy('name')->get(['id', 'name', 'municipality_id']),
        ]);
    }

    public function show(School $school): Response
    {
        $school->load(['district', 'municipality', 'barangay', 'learningResources.learningResourceType']);

        $activeSchoolYear = SchoolYear::active();

        $enrollments = $activeSchoolYear
            ? $school->enrollments()
                ->where('school_year_id', $activeSchoolYear->id)
                ->with('gradeLevel:id,name,sort_order')
                ->get()
                ->sortBy(fn (Enrollment $enrollment): int => $enrollment->gradeLevel?->sort_order ?? 0)
                ->values()
                ->map(fn (Enrollment $enrollment): array => [
                    'id' => $enrollment->id,
                    'grade_level' => $enrollment->gradeLevel?->name,
                    'male_count' => $enrollment->male_count,
                    'female_count' => $enrollment->female_count,
                    'total' => $enrollment->totalLearners(),
                ])
            : collect();

        return Inertia::render('AdminSchoolShow', [
            'school' => SchoolResource::make($school)->resolve(),
            'learningResources' => LearningResourceResource::collection($school->learningResources)->resolve(),
            'activeSchoolYear' => $activeSchoolYear?->only(['id', 'name']),
            'enrollments' => $enrollments,
            'generatedEmail' => session('generatedEmail'),
            'generatedPassword' => session('generatedPassword'),
        ]);
    }

    public function manuallyActivate(
        School $school,
        SchoolActivationService $activationService,
    ): RedirectResponse {
        if ($school->is_activated) {
            return redirect()
                ->route('admin.schools.show', $school)
                ->with('status', 'School is already activated.');
        }

        if (! $school->email || ! $school->school_head) {
            return redirect()
                ->route('admin.schools.show', $school)
                ->with('status', 'Activation request is incomplete. Ensure School Head and Email are provided before manual approval.');
        }

        try {
            $result = $activationService->activate($school, [
                'school_head' => $school->school_head,
                'librarian' => $school->librarian,
                'property_custodian' => $school->property_custodian,
                'primary_mobile_no' => $school->primary_mobile_no,
                'secondary_mobile_no' => $school->secondary_mobile_no,
                'email' => $school->email,
                'municipality_id' => $school->municipality_id,
                'district_id' => $school->district_id,
                'barangay_id' => $school->barangay_id,
            ]);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.schools.show', $school)
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('admin.schools.show', $school)
            ->with('status', 'School manually activated. Share the generated credentials with the school user.')
            ->with('generatedEmail', $result['user']->email)
            ->with('generatedPassword', $result['password']);
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
