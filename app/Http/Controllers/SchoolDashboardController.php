<?php

namespace App\Http\Controllers;

use App\Http\Resources\LearningResourceResource;
use App\Http\Resources\SchoolResource;
use App\Models\LearningResource;
use App\Models\LearningResourceType;
use App\Models\School;
use Illuminate\Database\Eloquent\Collection;
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
        ['school' => $school, 'learningResources' => $learningResources, 'learningResourceTypes' => $learningResourceTypes] = $this->resolveSchoolContext();

        if (! $this->isProfileComplete($school)) {
            return redirect()
                ->route('school.activate.edit', $school)
                ->with('status', 'Please update your school details first.');
        }

        $totalResources = $learningResources->count();
        $totalDelivered = (int) $learningResources->sum('quantity_delivered');
        $totalWithIssue = (int) $learningResources->sum('quantity_with_issue_defect');
        $issueRate = $totalDelivered > 0
            ? round(($totalWithIssue / $totalDelivered) * 100, 2)
            : 0;

        return Inertia::render('SchoolDashboard', [
            'school' => SchoolResource::make($school),
            'resourceSummary' => [
                'total_resources' => $totalResources,
                'total_delivered' => $totalDelivered,
                'total_with_issue' => $totalWithIssue,
                'issue_rate' => $issueRate,
                'learning_types_count' => count($learningResourceTypes),
            ],
        ]);
    }

    public function learningResources(): RedirectResponse|Response
    {
        ['school' => $school, 'learningResources' => $learningResources, 'learningResourceTypes' => $learningResourceTypes] = $this->resolveSchoolContext();

        if (! $this->isProfileComplete($school)) {
            return redirect()
                ->route('school.activate.edit', $school)
                ->with('status', 'Please update your school details first.');
        }

        return Inertia::render('SchoolLearningResources', [
            'school' => SchoolResource::make($school),
            'learningResources' => LearningResourceResource::collection($learningResources),
            'learningResourceTypes' => $learningResourceTypes,
        ]);
    }

    /**
     * @return array{school: School, learningResources: Collection<int, LearningResource>, learningResourceTypes: array<int, array{id: int, name: string}>}
     */
    private function resolveSchoolContext(): array
    {
        $user = auth()->user();
        $linkedSchool = $user?->school()->first(['id', 'school_id']);

        abort_if(! $linkedSchool, 403);

        $school = School::query()
            ->with(['district', 'municipality', 'barangay', 'learningResources.learningResourceType'])
            ->where('school_id', $linkedSchool->school_id)
            ->first();

        abort_if(! $school, 403);

        $learningResourceTypes = LearningResourceType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (LearningResourceType $type): array => [
                'id' => $type->id,
                'name' => $type->name,
            ])
            ->all();

        return [
            'school' => $school,
            'learningResources' => $school->learningResources,
            'learningResourceTypes' => $learningResourceTypes,
        ];
    }

    private function isProfileComplete(School $school): bool
    {
        return filled($school->school_head) && filled($school->email);
    }
}
