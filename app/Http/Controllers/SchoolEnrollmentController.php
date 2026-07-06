<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnrollmentsRequest;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\SchoolYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SchoolEnrollmentController extends Controller
{
    public function index(): Response
    {
        $school = $this->resolveSchool();
        $activeSchoolYear = SchoolYear::active();

        $enrollments = $activeSchoolYear
            ? $school->enrollments()
                ->where('school_year_id', $activeSchoolYear->id)
                ->get(['grade_level_id', 'male_count', 'female_count'])
                ->keyBy('grade_level_id')
                ->map(fn (Enrollment $enrollment): array => [
                    'male_count' => $enrollment->male_count,
                    'female_count' => $enrollment->female_count,
                ])
            : collect();

        return Inertia::render('SchoolEnrollment', [
            'activeSchoolYear' => $activeSchoolYear?->only(['id', 'name']),
            'gradeLevels' => GradeLevel::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name']),
            'enrollments' => $enrollments,
        ]);
    }

    public function store(StoreEnrollmentsRequest $request): RedirectResponse
    {
        $school = $this->resolveSchool();
        $activeSchoolYear = SchoolYear::active();

        if (! $activeSchoolYear) {
            return back()->withErrors([
                'enrollments' => 'No active school year is set. Please coordinate with your admin.',
            ]);
        }

        $entries = collect($request->validated('enrollments'))
            ->filter(fn (array $entry): bool => ((int) $entry['male_count'] + (int) $entry['female_count']) > 0)
            ->map(fn (array $entry): array => [
                'grade_level_id' => $entry['grade_level_id'],
                'male_count' => (int) $entry['male_count'],
                'female_count' => (int) $entry['female_count'],
                'school_year_id' => $activeSchoolYear->id,
            ])
            ->values()
            ->all();

        DB::transaction(function () use ($school, $activeSchoolYear, $entries): void {
            $school->enrollments()
                ->where('school_year_id', $activeSchoolYear->id)
                ->delete();

            $school->enrollments()->createMany($entries);
        });

        return back()->with('status', 'Enrollment saved successfully.');
    }

    private function resolveSchool(): School
    {
        $school = auth()->user()?->school;

        abort_if(! $school, 403);

        return $school;
    }
}
