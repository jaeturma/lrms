<?php

namespace App\Http\Controllers;

use App\Http\Resources\LearningResourceResource;
use App\Http\Resources\SchoolResource;
use App\Models\LearningResourceType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SchoolDashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): RedirectResponse|Response
    {
        $user = auth()->user();
        $school = $user?->school?->load(['district', 'municipality', 'barangay', 'learningResources']);

        abort_if(! $school, 403);

        $isProfileComplete = filled($school->school_head) && filled($school->email);

        if (! $isProfileComplete) {
            return redirect()
                ->route('school.activate.edit', $school)
                ->with('status', 'Please update your school details first.');
        }

        return Inertia::render('SchoolDashboard', [
            'school' => SchoolResource::make($school),
            'learningResources' => LearningResourceResource::collection($school->learningResources),
            'learningResourceTypes' => LearningResourceType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name')
                ->values(),
        ]);
    }
}
