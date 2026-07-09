<?php

namespace App\Http\Controllers;

use App\Http\Resources\LearningResourceResource;
use App\Http\Resources\SchoolResource;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\LearningResource;
use App\Models\LearningResourceType;
use App\Models\ResourceDistribution;
use App\Models\ResourceTitle;
use App\Models\School;
use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection as SupportCollection;
use Inertia\Inertia;
use Inertia\Response;

class SchoolDashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    private const REPAIR_CONDITIONS = ['Needs Repair', 'Beyond Repair'];

    private const GOOD_CONDITIONS = ['Excellent', 'Good'];

    public function __invoke(): RedirectResponse|Response
    {
        ['school' => $school, 'learningResources' => $learningResources] = $this->resolveSchoolContext();

        if (! $this->isProfileComplete($school)) {
            return redirect()
                ->route('school.activate.edit', $school)
                ->with('status', 'Please update your school details first.');
        }

        $activeSchoolYear = SchoolYear::active();

        $learners = $activeSchoolYear
            ? Enrollment::query()
                ->where('school_id', $school->id)
                ->where('school_year_id', $activeSchoolYear->id)
                ->selectRaw('COALESCE(SUM(male_count), 0) as male, COALESCE(SUM(female_count), 0) as female')
                ->first()
            : null;

        $totalDelivered = (int) $learningResources->sum('quantity_delivered');
        $totalWithIssue = (int) $learningResources->sum('quantity_with_issue_defect');

        $equipmentCondition = $this->equipmentCondition($school);
        $totalEquipment = $equipmentCondition->sum(fn (array $row): int => $row['good'] + $row['fair'] + $row['needs_attention']);
        $needingRepair = $equipmentCondition->sum('needs_attention');

        return Inertia::render('SchoolDashboard', [
            'school' => SchoolResource::make($school),
            'activeSchoolYear' => $activeSchoolYear?->only(['id', 'name']),
            'stats' => [
                'total_learners' => (int) ($learners->male ?? 0) + (int) ($learners->female ?? 0),
                'male_learners' => (int) ($learners->male ?? 0),
                'female_learners' => (int) ($learners->female ?? 0),
                'total_resources' => $learningResources->count(),
                'copies_delivered' => $totalDelivered,
                'copies_with_defects' => $totalWithIssue,
                'defect_rate' => $totalDelivered > 0 ? round(($totalWithIssue / $totalDelivered) * 100, 1) : 0,
                'total_equipment' => $totalEquipment,
                'equipment_needing_repair' => $needingRepair,
                'pending_distributions' => ResourceDistribution::query()->where('school_id', $school->id)->where('status', 'pending')->count(),
                'total_distributions' => ResourceDistribution::query()->where('school_id', $school->id)->count(),
            ],
            'enrollmentByGrade' => $this->enrollmentByGrade($school, $activeSchoolYear),
            'equipmentCondition' => $equipmentCondition,
        ]);
    }

    /**
     * @return SupportCollection<int, array<string, mixed>>
     */
    private function enrollmentByGrade(School $school, ?SchoolYear $activeSchoolYear): SupportCollection
    {
        if (! $activeSchoolYear) {
            return collect();
        }

        $totals = Enrollment::query()
            ->where('school_id', $school->id)
            ->where('school_year_id', $activeSchoolYear->id)
            ->selectRaw('grade_level_id, COALESCE(SUM(male_count), 0) as male, COALESCE(SUM(female_count), 0) as female')
            ->groupBy('grade_level_id')
            ->get()
            ->keyBy('grade_level_id');

        return GradeLevel::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name'])
            ->map(fn (GradeLevel $grade): array => [
                'grade' => $grade->name,
                'male' => (int) ($totals->get($grade->id)->male ?? 0),
                'female' => (int) ($totals->get($grade->id)->female ?? 0),
            ])
            ->filter(fn (array $row): bool => $row['male'] + $row['female'] > 0)
            ->values();
    }

    /**
     * @return SupportCollection<int, array<string, mixed>>
     */
    private function equipmentCondition(School $school): SupportCollection
    {
        $buckets = [
            'ICT Equipment' => $school->ictEquipment(),
            'Science & Math' => $school->sme(),
            'Other Equipment' => $school->otherEquipment(),
        ];

        return collect($buckets)->map(function ($query, string $type): array {
            $rows = $query->selectRaw('`condition`, COUNT(*) as total')->groupBy('condition')->pluck('total', 'condition');

            return [
                'type' => $type,
                'good' => (int) collect(self::GOOD_CONDITIONS)->sum(fn (string $condition): int => (int) $rows->get($condition, 0)),
                'fair' => (int) $rows->get('Fair', 0),
                'needs_attention' => (int) collect(self::REPAIR_CONDITIONS)->sum(fn (string $condition): int => (int) $rows->get($condition, 0)),
            ];
        })->values();
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
            'resourceTitles' => ResourceTitle::query()
                ->where('is_active', true)
                ->with(['learningResourceType:id,name', 'gradeLevel:id,name'])
                ->orderBy('title')
                ->get()
                ->map(fn (ResourceTitle $resourceTitle): array => [
                    'id' => $resourceTitle->id,
                    'title' => $resourceTitle->title,
                    'author' => $resourceTitle->author,
                    'publisher' => $resourceTitle->publisher,
                    'language' => $resourceTitle->language,
                    'subject' => $resourceTitle->subject,
                    'resource_type' => $resourceTitle->learningResourceType?->name,
                    'grade_level' => $resourceTitle->gradeLevel?->name,
                    'isbn' => $resourceTitle->isbn,
                    'cover_image_url' => $resourceTitle->coverImageUrl(),
                    'attachment_url' => $resourceTitle->attachmentUrl(),
                    'media_url' => $resourceTitle->media_url,
                ]),
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
            ->with(['district', 'municipality', 'barangay', 'learningResources.learningResourceType', 'learningResources.resourceTitle'])
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
