<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DigitalLearningMaterial;
use App\Models\District;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\IctEquipment;
use App\Models\LearningResource;
use App\Models\Municipality;
use App\Models\OtherEquipment;
use App\Models\ResourceDistribution;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Sme;
use App\Models\ValidLrSchool;
use App\Models\ValidLrVisit;
use App\Models\ValidLrVisitItem;
use App\Services\ValidLrPreparationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    private const REPAIR_CONDITIONS = ['Needs Repair', 'Beyond Repair'];

    private const GOOD_CONDITIONS = ['Excellent', 'Good'];

    private const FUNCTIONAL_STATUSES = ['Available', 'In Use', 'Borrowed'];

    private const NON_FUNCTIONAL_STATUSES = ['Missing', 'Lost', 'Condemned', 'Disposed'];

    private const SME_LAB_CATEGORIES = ['Science', 'Mathematics'];

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, ValidLrPreparationService $preparationService): Response
    {
        $search = $request->string('search')->toString();
        $districtId = $request->integer('district_id');
        $schoolType = $request->string('school_type')->toString();
        $gradeLevelId = $request->integer('grade_level_id');

        // Entire division = no school scope; otherwise resolve the scoped set once.
        $schoolIds = ($districtId > 0 || $schoolType !== '')
            ? School::query()
                ->when($districtId > 0, fn ($query) => $query->where('district_id', $districtId))
                ->when($schoolType !== '', fn ($query) => $query->where('school_type', $schoolType))
                ->pluck('id')
            : null;

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
            ->when($schoolType !== '', fn ($query) => $query->where('school_type', $schoolType))
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
                'learning_resources_count' => $school->learning_resources_count,
            ]);

        $activeSchoolYear = SchoolYear::active();

        return Inertia::render('AdminDashboard', [
            'stats' => $this->stats($activeSchoolYear, $schoolIds, $gradeLevelId),
            'enrollmentByGrade' => $this->enrollmentByGrade($activeSchoolYear, $schoolIds, $gradeLevelId),
            'activationByMunicipality' => $this->activationByMunicipality($schoolIds),
            'equipmentCondition' => $this->equipmentCondition($schoolIds),
            'defectRateByMunicipality' => $this->defectRateByMunicipality($schoolIds, $gradeLevelId),
            'pendingActivations' => $this->pendingActivations($schoolIds),
            'learnerEquipmentRatio' => $this->learnerEquipmentRatioByMunicipality($activeSchoolYear, $schoolIds),
            'smeLabAdequacy' => $this->smeLabAdequacyByMunicipality($schoolIds),
            'equipmentFunctionalStatus' => $this->equipmentFunctionalStatus($schoolIds),
            'districtResourceHeatmap' => $this->districtResourceHeatmap($schoolIds),
            'lrMonitoring' => $this->lrMonitoring($preparationService),
            'activeSchoolYear' => $activeSchoolYear?->only(['id', 'name']),
            'districts' => District::query()->orderBy('name')->get(['id', 'name']),
            'schoolTypes' => School::SCHOOL_TYPES,
            'gradeLevels' => GradeLevel::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'district_id' => $districtId > 0 ? $districtId : null,
                'school_type' => $schoolType !== '' ? $schoolType : null,
                'grade_level_id' => $gradeLevelId > 0 ? $gradeLevelId : null,
            ],
            'schools' => $schools,
        ]);
    }

    /**
     * @param  Collection<int, int>|null  $schoolIds
     * @return array<string, int|float>
     */
    private function stats(?SchoolYear $activeSchoolYear, ?Collection $schoolIds, int $gradeLevelId): array
    {
        $scopeSchools = fn ($query) => $query->when($schoolIds !== null, fn ($scoped) => $scoped->whereIn('id', $schoolIds));
        $scopeBySchool = fn ($query) => $query->when($schoolIds !== null, fn ($scoped) => $scoped->whereIn('school_id', $schoolIds));

        $learners = $activeSchoolYear
            ? $scopeBySchool(Enrollment::query())
                ->where('school_year_id', $activeSchoolYear->id)
                ->when($gradeLevelId > 0, fn ($query) => $query->where('grade_level_id', $gradeLevelId))
                ->selectRaw('COALESCE(SUM(male_count), 0) as male, COALESCE(SUM(female_count), 0) as female')
                ->first()
            : null;

        $resources = $scopeBySchool(LearningResource::query())
            ->when($gradeLevelId > 0, fn ($query) => $query->where('grade_level_id', $gradeLevelId))
            ->selectRaw('COALESCE(SUM(quantity_delivered), 0) as delivered, COALESCE(SUM(quantity_with_issue_defect), 0) as defective')
            ->first();

        $delivered = (int) ($resources->delivered ?? 0);
        $defective = (int) ($resources->defective ?? 0);

        $needingRepair = $scopeBySchool(IctEquipment::query())->whereIn('condition', self::REPAIR_CONDITIONS)->count()
            + $scopeBySchool(OtherEquipment::query())->whereIn('condition', self::REPAIR_CONDITIONS)->count()
            + $scopeBySchool(Sme::query())->whereIn('condition', self::REPAIR_CONDITIONS)->count();

        return [
            'total_schools' => $scopeSchools(School::query())->count(),
            'activated_schools' => $scopeSchools(School::query())->where('is_activated', true)->count(),
            'pending_requests' => $scopeSchools(School::query())->where('is_activated', false)->whereNotNull('activation_requested_at')->count(),
            'total_learners' => (int) ($learners->male ?? 0) + (int) ($learners->female ?? 0),
            'male_learners' => (int) ($learners->male ?? 0),
            'female_learners' => (int) ($learners->female ?? 0),
            'copies_delivered' => $delivered,
            'copies_with_defects' => $defective,
            'defect_rate' => $delivered > 0 ? round(($defective / $delivered) * 100, 1) : 0,
            'total_equipment' => $scopeBySchool(IctEquipment::query())->count()
                + $scopeBySchool(OtherEquipment::query())->count()
                + $scopeBySchool(Sme::query())->count(),
            'equipment_needing_repair' => $needingRepair,
            'digital_lms' => DigitalLearningMaterial::count(),
            'digital_lms_quality_assured' => DigitalLearningMaterial::where('quality_assured', true)->count(),
            'pending_distributions' => $scopeBySchool(ResourceDistribution::query())->where('status', 'pending')->count(),
            'total_distributions' => $scopeBySchool(ResourceDistribution::query())->count(),
        ];
    }

    /**
     * @param  Collection<int, int>|null  $schoolIds
     * @return Collection<int, array<string, mixed>>
     */
    private function enrollmentByGrade(?SchoolYear $activeSchoolYear, ?Collection $schoolIds, int $gradeLevelId): Collection
    {
        if (! $activeSchoolYear) {
            return collect();
        }

        $totals = Enrollment::query()
            ->where('school_year_id', $activeSchoolYear->id)
            ->when($schoolIds !== null, fn ($query) => $query->whereIn('school_id', $schoolIds))
            ->when($gradeLevelId > 0, fn ($query) => $query->where('grade_level_id', $gradeLevelId))
            ->selectRaw('grade_level_id, COALESCE(SUM(male_count), 0) as male, COALESCE(SUM(female_count), 0) as female')
            ->groupBy('grade_level_id')
            ->get()
            ->keyBy('grade_level_id');

        return GradeLevel::query()
            ->where('is_active', true)
            ->when($gradeLevelId > 0, fn ($query) => $query->whereKey($gradeLevelId))
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
     * @param  Collection<int, int>|null  $schoolIds
     * @return Collection<int, array<string, mixed>>
     */
    private function activationByMunicipality(?Collection $schoolIds): Collection
    {
        $constraint = fn ($query) => $query->when($schoolIds !== null, fn ($scoped) => $scoped->whereIn('id', $schoolIds));

        return Municipality::query()
            ->withCount([
                'schools as total' => $constraint,
                'schools as activated' => fn ($query) => $constraint($query)->where('is_activated', true),
            ])
            ->orderBy('name')
            ->get()
            ->filter(fn (Municipality $municipality): bool => (int) $municipality->total > 0)
            ->map(fn (Municipality $municipality): array => [
                'municipality' => $municipality->name,
                'activated' => (int) $municipality->activated,
                'total' => (int) $municipality->total,
            ])
            ->values();
    }

    /**
     * @param  Collection<int, int>|null  $schoolIds
     * @return Collection<int, array<string, mixed>>
     */
    private function equipmentCondition(?Collection $schoolIds): Collection
    {
        $buckets = [
            'ICT Equipment' => IctEquipment::query(),
            'Science & Math' => Sme::query(),
            'Other Equipment' => OtherEquipment::query(),
        ];

        return collect($buckets)->map(function ($query, string $type) use ($schoolIds): array {
            $rows = $query
                ->when($schoolIds !== null, fn ($scoped) => $scoped->whereIn('school_id', $schoolIds))
                ->selectRaw('`condition`, COUNT(*) as total')
                ->groupBy('condition')
                ->pluck('total', 'condition');

            return [
                'type' => $type,
                'good' => (int) collect(self::GOOD_CONDITIONS)->sum(fn (string $condition): int => (int) $rows->get($condition, 0)),
                'fair' => (int) $rows->get('Fair', 0),
                'needs_attention' => (int) collect(self::REPAIR_CONDITIONS)->sum(fn (string $condition): int => (int) $rows->get($condition, 0)),
            ];
        })->values();
    }

    /**
     * @param  Collection<int, int>|null  $schoolIds
     * @return Collection<int, array<string, mixed>>
     */
    private function defectRateByMunicipality(?Collection $schoolIds, int $gradeLevelId): Collection
    {
        return LearningResource::query()
            ->join('schools', 'schools.id', '=', 'learning_resources.school_id')
            ->join('municipalities', 'municipalities.id', '=', 'schools.municipality_id')
            ->whereNull('schools.deleted_at')
            ->when($schoolIds !== null, fn ($query) => $query->whereIn('learning_resources.school_id', $schoolIds))
            ->when($gradeLevelId > 0, fn ($query) => $query->where('learning_resources.grade_level_id', $gradeLevelId))
            ->selectRaw('municipalities.name as municipality, COALESCE(SUM(learning_resources.quantity_delivered), 0) as delivered, COALESCE(SUM(learning_resources.quantity_with_issue_defect), 0) as defective')
            ->groupBy('municipalities.name')
            ->havingRaw('SUM(learning_resources.quantity_delivered) > 0')
            ->get()
            ->map(fn ($row): array => [
                'municipality' => $row->municipality,
                'delivered' => (int) $row->delivered,
                'defective' => (int) $row->defective,
                'rate' => round(((int) $row->defective / max(1, (int) $row->delivered)) * 100, 1),
            ])
            ->sortByDesc('rate')
            ->values();
    }

    /**
     * @param  Collection<int, int>|null  $schoolIds
     * @return Collection<int, array<string, mixed>>
     */
    private function pendingActivations(?Collection $schoolIds): Collection
    {
        return School::query()
            ->with('district:id,name')
            ->when($schoolIds !== null, fn ($query) => $query->whereIn('id', $schoolIds))
            ->where('is_activated', false)
            ->whereNotNull('activation_requested_at')
            ->orderByDesc('activation_requested_at')
            ->limit(5)
            ->get()
            ->map(fn (School $school): array => [
                'school_id' => $school->school_id,
                'school_name' => $school->school_name,
                'district' => $school->district?->name,
                'requested_at' => $school->activation_requested_at?->toIso8601String(),
            ]);
    }

    /**
     * @param  Collection<int, int>|null  $schoolIds
     * @return Collection<int, array<string, mixed>>
     */
    private function learnerEquipmentRatioByMunicipality(?SchoolYear $activeSchoolYear, ?Collection $schoolIds): Collection
    {
        $learners = $activeSchoolYear
            ? $this->sumByMunicipality(Enrollment::class, 'enrollments', $schoolIds, function ($query) use ($activeSchoolYear): void {
                $query->where('enrollments.school_year_id', $activeSchoolYear->id);
            }, 'enrollments.male_count + enrollments.female_count')
            : collect();

        $ict = $this->countByMunicipality(IctEquipment::class, 'ict_equipment', $schoolIds);
        $sme = $this->countByMunicipality(Sme::class, 'sme', $schoolIds);
        $other = $this->countByMunicipality(OtherEquipment::class, 'other_equipment', $schoolIds);

        return Municipality::query()
            ->orderBy('name')
            ->get()
            ->map(function (Municipality $municipality) use ($learners, $ict, $sme, $other): array {
                $learnerCount = (int) ($learners->get($municipality->id) ?? 0);
                $equipmentCount = (int) ($ict->get($municipality->id) ?? 0)
                    + (int) ($sme->get($municipality->id) ?? 0)
                    + (int) ($other->get($municipality->id) ?? 0);

                return [
                    'municipality' => $municipality->name,
                    'learners' => $learnerCount,
                    'equipment' => $equipmentCount,
                    'ratio' => $equipmentCount > 0 ? round($learnerCount / $equipmentCount, 1) : null,
                ];
            })
            ->filter(fn (array $row): bool => $row['learners'] > 0 || $row['equipment'] > 0)
            ->values();
    }

    /**
     * @param  Collection<int, int>|null  $schoolIds
     * @return Collection<int, array<string, mixed>>
     */
    private function smeLabAdequacyByMunicipality(?Collection $schoolIds): Collection
    {
        $schools = School::query()
            ->when($schoolIds !== null, fn ($query) => $query->whereIn('id', $schoolIds))
            ->with('municipality:id,name')
            ->get(['id', 'municipality_id']);

        $functionalCategories = Sme::query()
            ->whereIn('school_id', $schools->pluck('id'))
            ->whereNotIn('condition', self::REPAIR_CONDITIONS)
            ->select('school_id', 'category')
            ->distinct()
            ->get()
            ->groupBy('school_id')
            ->map(fn (Collection $rows): Collection => $rows->pluck('category'));

        return $schools
            ->groupBy('municipality_id')
            ->map(function (Collection $municipalitySchools) use ($functionalCategories): array {
                $adequate = $municipalitySchools->filter(function (School $school) use ($functionalCategories): bool {
                    $categories = $functionalCategories->get($school->id, collect());

                    return collect(self::SME_LAB_CATEGORIES)->every(fn (string $category): bool => $categories->contains($category));
                })->count();

                return [
                    'municipality' => $municipalitySchools->first()->municipality?->name ?? 'Unassigned',
                    'adequate' => $adequate,
                    'total' => $municipalitySchools->count(),
                ];
            })
            ->sortBy('municipality')
            ->values();
    }

    /**
     * @param  Collection<int, int>|null  $schoolIds
     * @return Collection<int, array<string, mixed>>
     */
    private function equipmentFunctionalStatus(?Collection $schoolIds): Collection
    {
        $buckets = [
            'ICT Equipment' => IctEquipment::query(),
            'Science & Math' => Sme::query(),
            'Other Equipment' => OtherEquipment::query(),
        ];

        return collect($buckets)->map(function ($query, string $type) use ($schoolIds): array {
            $rows = $query
                ->when($schoolIds !== null, fn ($scoped) => $scoped->whereIn('school_id', $schoolIds))
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            return [
                'type' => $type,
                'functional' => (int) collect(self::FUNCTIONAL_STATUSES)->sum(fn (string $status): int => (int) $rows->get($status, 0)),
                'non_functional' => (int) collect(self::NON_FUNCTIONAL_STATUSES)->sum(fn (string $status): int => (int) $rows->get($status, 0)),
            ];
        })->values();
    }

    /**
     * @param  Collection<int, int>|null  $schoolIds
     * @return array{districts: array<int, string>, resourceTypes: array<int, string>, values: array<int, array<int, int>>}
     */
    private function districtResourceHeatmap(?Collection $schoolIds): array
    {
        $districts = District::query()
            ->when($schoolIds !== null, fn ($query) => $query->whereHas('schools', fn ($schoolQuery) => $schoolQuery->whereIn('id', $schoolIds)))
            ->orderBy('name')
            ->get();

        $printed = $this->sumByDistrict(LearningResource::class, 'learning_resources', $schoolIds, quantityColumn: 'learning_resources.quantity_delivered');
        $ict = $this->countByDistrict(IctEquipment::class, 'ict_equipment', $schoolIds);
        $sme = $this->countByDistrict(Sme::class, 'sme', $schoolIds);
        $other = $this->countByDistrict(OtherEquipment::class, 'other_equipment', $schoolIds);

        return [
            'districts' => $districts->pluck('name')->values()->all(),
            'resourceTypes' => ['Printed Resources', 'ICT Equipment', 'Science & Math', 'Other Equipment'],
            'values' => $districts->map(fn (District $district): array => [
                (int) ($printed->get($district->id) ?? 0),
                (int) ($ict->get($district->id) ?? 0),
                (int) ($sme->get($district->id) ?? 0),
                (int) ($other->get($district->id) ?? 0),
            ])->values()->all(),
        ];
    }

    /**
     * Division-wide LR Monitoring (VALID-LR) progress: visit stages, coverage
     * by district, count accuracy and the validations happening now.
     *
     * @return array<string, mixed>
     */
    private function lrMonitoring(ValidLrPreparationService $preparationService): array
    {
        $completedSchoolIds = ValidLrVisit::query()->where('status', 'completed')->distinct()->pluck('valid_lr_school_id');
        $monitoredSchoolIds = ValidLrVisit::query()->distinct()->pluck('valid_lr_school_id');

        $districts = ValidLrSchool::query()
            ->get(['id', 'district'])
            ->groupBy(fn (ValidLrSchool $school): string => $school->district ?: 'Unassigned')
            ->map(fn (Collection $schools, string $district): array => [
                'district' => $district,
                'schools' => $schools->count(),
                'monitored' => $schools->pluck('id')->intersect($monitoredSchoolIds)->count(),
                'completed' => $schools->pluck('id')->intersect($completedSchoolIds)->count(),
            ])
            ->sortKeys()
            ->values();

        $accuracy = ValidLrVisitItem::query()
            ->whereIn('section', ValidLrVisit::COUNTED_SECTIONS)
            ->whereNotNull('actual_quantity')
            ->selectRaw('count(*) as validated')
            ->selectRaw('sum(case when (case when section = ? then lrmis_quantity else reference_quantity end) is not null and actual_quantity <> (case when section = ? then lrmis_quantity else reference_quantity end) then 1 else 0 end) as discrepancies', [
                ValidLrVisitItem::SECTION_LEARNING_RESOURCES,
                ValidLrVisitItem::SECTION_LEARNING_RESOURCES,
            ])
            ->first();

        $ongoingVisits = ValidLrVisit::query()
            ->inStage(ValidLrVisit::STAGE_VALIDATING)
            ->with(['validLrSchool:id,beis_school_id,school_name,district', 'teamLeader:id,name'])
            ->latest('updated_at')
            ->limit(5)
            ->get();
        $progress = $preparationService->progress($ongoingVisits->pluck('id'));

        return [
            'schools' => ValidLrSchool::query()->count(),
            'schools_monitored' => $monitoredSchoolIds->count(),
            'schools_completed' => $completedSchoolIds->count(),
            'not_started' => ValidLrSchool::query()->doesntHave('visits')->count(),
            'stages' => collect(ValidLrVisit::STAGES)
                ->map(fn (string $label, string $stage): array => [
                    'stage' => $stage,
                    'label' => $label,
                    'count' => ValidLrVisit::query()->inStage($stage)->count(),
                ])
                ->values(),
            'districts' => $districts,
            'items_validated' => (int) ($accuracy->validated ?? 0),
            'items_with_discrepancy' => (int) ($accuracy->discrepancies ?? 0),
            'ongoing' => $ongoingVisits->map(function (ValidLrVisit $visit) use ($progress): array {
                $counts = $progress->get($visit->id, ['items_total' => 0, 'validated' => 0]);

                return [
                    'id' => $visit->id,
                    'school_name' => $visit->validLrSchool->school_name,
                    'beis_school_id' => $visit->validLrSchool->beis_school_id,
                    'district' => $visit->validLrSchool->district,
                    'team_leader' => $visit->teamLeader?->name,
                    'items_total' => $counts['items_total'],
                    'validated' => $counts['validated'],
                    'updated_at' => $visit->updated_at?->diffForHumans(),
                ];
            })->values(),
        ];
    }

    /**
     * @param  class-string  $modelClass
     * @param  Collection<int, int>|null  $schoolIds
     * @return Collection<int, int>
     */
    private function countByMunicipality(string $modelClass, string $table, ?Collection $schoolIds): Collection
    {
        return $modelClass::query()
            ->join('schools', 'schools.id', '=', "{$table}.school_id")
            ->whereNull('schools.deleted_at')
            ->when($schoolIds !== null, fn ($query) => $query->whereIn("{$table}.school_id", $schoolIds))
            ->selectRaw('schools.municipality_id, COUNT(*) as total')
            ->groupBy('schools.municipality_id')
            ->pluck('total', 'schools.municipality_id');
    }

    /**
     * @param  class-string  $modelClass
     * @param  Collection<int, int>|null  $schoolIds
     * @return Collection<int, int>
     */
    private function countByDistrict(string $modelClass, string $table, ?Collection $schoolIds): Collection
    {
        return $modelClass::query()
            ->join('schools', 'schools.id', '=', "{$table}.school_id")
            ->whereNull('schools.deleted_at')
            ->when($schoolIds !== null, fn ($query) => $query->whereIn("{$table}.school_id", $schoolIds))
            ->selectRaw('schools.district_id, COUNT(*) as total')
            ->groupBy('schools.district_id')
            ->pluck('total', 'schools.district_id');
    }

    /**
     * @param  class-string  $modelClass
     * @param  Collection<int, int>|null  $schoolIds
     * @return Collection<int, int>
     */
    private function sumByDistrict(string $modelClass, string $table, ?Collection $schoolIds, string $quantityColumn): Collection
    {
        return $modelClass::query()
            ->join('schools', 'schools.id', '=', "{$table}.school_id")
            ->whereNull('schools.deleted_at')
            ->when($schoolIds !== null, fn ($query) => $query->whereIn("{$table}.school_id", $schoolIds))
            ->selectRaw("schools.district_id, COALESCE(SUM({$quantityColumn}), 0) as total")
            ->groupBy('schools.district_id')
            ->pluck('total', 'schools.district_id');
    }

    /**
     * @param  class-string  $modelClass
     * @param  Collection<int, int>|null  $schoolIds
     * @param  (callable(Builder): void)  $constraint
     * @return Collection<int, int>
     */
    private function sumByMunicipality(string $modelClass, string $table, ?Collection $schoolIds, callable $constraint, string $quantityColumn): Collection
    {
        return tap($modelClass::query()
            ->join('schools', 'schools.id', '=', "{$table}.school_id")
            ->whereNull('schools.deleted_at')
            ->when($schoolIds !== null, fn ($query) => $query->whereIn("{$table}.school_id", $schoolIds)), $constraint)
            ->selectRaw("schools.municipality_id, COALESCE(SUM({$quantityColumn}), 0) as total")
            ->groupBy('schools.municipality_id')
            ->pluck('total', 'schools.municipality_id');
    }
}
