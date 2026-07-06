<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGradeLevelRequest;
use App\Models\GradeLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GradeLevelController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('AdminGradeLevels', [
            'gradeLevels' => GradeLevel::query()
                ->withCount('enrollments')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'sort_order', 'is_active']),
        ]);
    }

    public function store(StoreGradeLevelRequest $request): RedirectResponse
    {
        GradeLevel::query()->create([
            ...$request->validated(),
            'is_active' => true,
        ]);

        return back()->with('status', 'Grade level added.');
    }

    public function update(Request $request, GradeLevel $gradeLevel): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('grade_levels', 'name')->ignore($gradeLevel->id),
            ],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $gradeLevel->update($validated);

        return back()->with('status', 'Grade level updated.');
    }

    public function destroy(GradeLevel $gradeLevel): RedirectResponse
    {
        if ($gradeLevel->enrollments()->exists()) {
            return back()->withErrors([
                'name' => 'This grade level has enrollment records. Mark it inactive instead of deleting it.',
            ]);
        }

        $gradeLevel->delete();

        return back()->with('status', 'Grade level deleted.');
    }
}
