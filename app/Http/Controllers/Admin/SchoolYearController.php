<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSchoolYearRequest;
use App\Models\SchoolYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SchoolYearController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('AdminSchoolYears', [
            'schoolYears' => SchoolYear::query()
                ->withCount('enrollments')
                ->orderByDesc('name')
                ->get()
                ->map(fn (SchoolYear $schoolYear): array => [
                    'id' => $schoolYear->id,
                    'name' => $schoolYear->name,
                    'starts_on' => $schoolYear->starts_on?->toDateString(),
                    'ends_on' => $schoolYear->ends_on?->toDateString(),
                    'is_active' => $schoolYear->is_active,
                    'enrollments_count' => $schoolYear->enrollments_count,
                ]),
        ]);
    }

    public function store(StoreSchoolYearRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $makeActive = SchoolYear::query()->where('is_active', true)->doesntExist();

            SchoolYear::query()->create([
                ...$validated,
                'is_active' => $makeActive,
            ]);
        });

        return back()->with('status', 'School year added.');
    }

    public function update(Request $request, SchoolYear $schoolYear): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:20',
                'regex:/^\d{4}-\d{4}$/',
                Rule::unique('school_years', 'name')->ignore($schoolYear->id),
            ],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ]);

        $schoolYear->update($validated);

        return back()->with('status', 'School year updated.');
    }

    public function activate(SchoolYear $schoolYear): RedirectResponse
    {
        DB::transaction(function () use ($schoolYear): void {
            SchoolYear::query()->where('is_active', true)->update(['is_active' => false]);
            $schoolYear->update(['is_active' => true]);
        });

        return back()->with('status', "School year {$schoolYear->name} is now active.");
    }

    public function destroy(SchoolYear $schoolYear): RedirectResponse
    {
        if ($schoolYear->enrollments()->exists()) {
            return back()->withErrors([
                'name' => 'This school year has enrollment records and cannot be deleted.',
            ]);
        }

        if ($schoolYear->is_active) {
            return back()->withErrors([
                'name' => 'The active school year cannot be deleted. Activate another school year first.',
            ]);
        }

        $schoolYear->delete();

        return back()->with('status', 'School year deleted.');
    }
}
