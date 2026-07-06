<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLearningResourceTypeRequest;
use App\Models\LearningResourceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LearningResourceTypeController extends Controller
{
    public function store(StoreLearningResourceTypeRequest $request): RedirectResponse
    {
        LearningResourceType::query()->create([
            'name' => $request->validated('name'),
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
            'is_active' => ['required', 'boolean'],
        ]);

        $learningResourceType->update($validated);

        return back()->with('status', 'Learning material type updated.');
    }

    public function destroy(LearningResourceType $learningResourceType): RedirectResponse
    {
        $learningResourceType->delete();

        return back()->with('status', 'Learning material type deleted.');
    }
}
