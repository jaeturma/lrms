<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLearningResourceTypeRequest;
use App\Models\LearningResourceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LearningResourceTypeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('AdminLearningResourceTypes', [
            'learningResourceTypes' => LearningResourceType::query()
                ->orderBy('name')
                ->get(['id', 'name', 'category', 'is_active']),
            'categories' => LearningResourceType::CATEGORIES,
        ]);
    }

    public function store(StoreLearningResourceTypeRequest $request): RedirectResponse
    {
        LearningResourceType::query()->create([
            'name' => $request->validated('name'),
            'category' => $request->validated('category'),
            'is_active' => true,
        ]);

        return back()->with('status', 'Learning material type added.');
    }

    public function update(Request $request, LearningResourceType $learningResourceType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('learning_resource_types', 'name')->ignore($learningResourceType->id),
            ],
            'category' => ['required', 'string', Rule::in(LearningResourceType::CATEGORIES)],
            'is_active' => ['required', 'boolean'],
        ]);

        $learningResourceType->update($validated);

        return back()->with('status', 'Learning material type updated.');
    }

    public function destroy(LearningResourceType $learningResourceType): RedirectResponse
    {
        if ($learningResourceType->learningResources()->exists()) {
            return back()->withErrors([
                'name' => 'This type is used by existing learning resources. Mark it inactive instead of deleting it.',
            ]);
        }

        $learningResourceType->delete();

        return back()->with('status', 'Learning material type deleted.');
    }
}
