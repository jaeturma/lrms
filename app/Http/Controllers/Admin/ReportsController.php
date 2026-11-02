<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Enrollment;
use App\Models\IctEquipment;
use App\Models\LearningResource;
use App\Models\Municipality;
use App\Models\OtherEquipment;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Sme;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    private const REPAIR_CONDITIONS = ['Needs Repair', 'Beyond Repair'];

    private const FUNCTIONAL_STATUSES = ['Available', 'In Use', 'Borrowed'];

    private const NON_FUNCTIONAL_STATUSES = ['Missing', 'Lost', 'Condemned', 'Disposed'];

    private const SME_LAB_CATEGORIES = ['Science', 'Mathematics'];

    public function index(Request $request): Response
    {
        $schoolYearId = $this->resolveSchoolYearId($request);
        $districtId = $request->integer('district_id');
        $municipalityId = $request->integer('municipality_id');

        $adequacy = $this->resourceAdequacyRows($schoolYearId, $districtId, $municipalityId);

        return Inertia::render('AdminReports', [
            'filters' => [
                'school_year_id' => $schoolYearId,
                'district_id' => $districtId > 0 ? $districtId : null,
                'municipality_id' => $municipalityId > 0 ? $municipalityId : null,
            ],
            'schoolYears' => SchoolYear::query()->orderByDesc('starts_on')->get(['id', 'name']),
            'districts' => District::query()->orderBy('name')->get(['id', 'name']),
            'municipalities' => Municipality::query()->orderBy('name')->get(['id', 'name']),
            'resourceAdequacy' => $adequacy,
            'resourceSummary' => [
                'total_learners' => $adequacy->sum('learners'),
                'total_available' => $adequacy->sum('available_copies'),
                'schools_in_shortage' => $adequacy->where('shortage', '>', 0)->count(),
            ],
            'ictEquipmentByCategory' => $this->categoryBreakdown(IctEquipment::class, $districtId, $municipalityId),
            'ictEquipmentByStatus' => $this->statusBreakdown(IctEquipment::class, $districtId, $municipalityId),
            'otherEquipmentByCategory' => $this->categoryBreakdown(OtherEquipment::class, $districtId, $municipalityId),
            'otherEquipmentByStatus' => $this->statusBreakdown(OtherEquipment::class, $districtId, $municipalityId),
            'equipmentConditions' => IctEquipment::CONDITIONS,
            'learnerEquipmentRatio' => $this->learnerEquipmentRatioByMunicipality($schoolYearId, $districtId, $municipalityId),
            'smeLabAdequacy' => $this->smeLabAdequacyByMunicipality($districtId, $municipalityId),
            'equipmentFunctionalStatus' => $this->equipmentFunctionalStatus($districtId, $municipalityId),
            'districtResourceHeatmap' => $this->districtResourceHeatmap($districtId, $municipalityId),
        ]);
    }

    public function exportLearningResources(Request $request): StreamedResponse
    {
        $schoolYearId = $this->resolveSchoolYearId($request);

        $rows = $this
            ->resourceAdequacyRows($schoolYearId, $request->integer('district_id'), $request->integer('municipality_id'))
            ->map(fn (array $row): array => [
                $row['school_id'],
                $row['school_name'],
                $row['district'],
                $row['municipality'],
                $row['learners'],
                $row['available_copies'],
                $row['copies_per_learner'],
                $row['shortage'],
            ]);

        return $this->streamCsv(
            'learning-resource-adequacy.csv',
            ['School ID', 'School Name', 'District', 'Municipality', 'Learners', 'Available Copies', 'Copies per Learner', 'Shortage'],
            $rows,
        );
    }

    public function exportIctEquipment(Request $request): StreamedResponse
    {
        return $this->exportEquipmentSummary(
            IctEquipment::class,
            'ict_equipment',
            $request,
            'ict-equipment-summary.csv',
        );
    }

    public function exportOtherEquipment(Request $request): StreamedResponse
    {
        return $this->exportEquipmentSummary(
            OtherEquipment::class,
            'other_equipment',
            $request,
            'other-equipment-summary.csv',
        );
    }

    public function exportSme(Request $request): StreamedResponse
    {
        $rows = $this
            ->smeQuery($request->integer('district_id'), $request->integer('municipality_id'))
            ->join('schools', 'schools.id', '=', 'sme.school_id')
            ->whereNull('schools.deleted_at')
            ->select('schools.school_id', 'schools.school_name', 'sme.category', 'sme.condition', 'sme.status')
            ->selectRaw('count(*) as total')
            ->groupBy('schools.school_id', 'schools.school_name', 'sme.category', 'sme.condition', 'sme.status')
            ->orderBy('schools.school_name')
            ->orderBy('sme.category')
            ->get()
            ->map(fn (Sme $row): array => [
                $row->school_id,
                $row->school_name,
                $row->category,
                $row->condition,
                $row->status,
                $row->total,
            ]);

        return $this->streamCsv(
            'sme-summary.csv',
            ['School ID', 'School Name', 'Category', 'Condition', 'Status', 'Total'],
            $rows,
        );
    }

    /**
     * Build per-school learning resource adequacy rows comparing the copies
     * available in inventories against the enrollment of the school year.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function resourceAdequacyRows(?int $schoolYearId, int $districtId, int $municipalityId): Collection
    {
        return School::query()
            ->with(['district:id,name', 'municipality:id,name'])
            ->withSum([
                'enrollments as male_learners' => fn (Builder $query) => $query->where('school_year_id', $schoolYearId ?? 0),
            ], 'male_count')
            ->withSum([
                'enrollments as female_learners' => fn (Builder $query) => $query->where('school_year_id', $schoolYearId ?? 0),
            ], 'female_count')
            ->withSum('learningResourceInventories as available_copies', 'available')
            ->when($districtId > 0, fn (Builder $query) => $query->where('district_id', $districtId))
            ->when($municipalityId > 0, fn (Builder $query) => $query->where('municipality_id', $municipalityId))
            ->orderBy('school_name')
            ->get()
            ->map(function (School $school): array {
                $learners = (int) $school->male_learners + (int) $school->female_learners;
                $available = (int) $school->available_copies;

                return [
                    'school_id' => $school->school_id,
                    'school_name' => $school->school_name,
                    'district' => $school->district?->name,
                    'municipality' => $school->municipality?->name,
                    'learners' => $learners,
                    'available_copies' => $available,
                    'copies_per_learner' => $learners > 0 ? round($available / $learners, 2) : null,
                    'shortage' => max(0, $learners - $available),
                ];
            });
    }

    /**
     * @param  class-string<IctEquipment|OtherEquipment>  $modelClass
     * @return Collection<int, array<string, mixed>>
     */
    private function categoryBreakdown(string $modelClass, int $districtId, int $municipalityId): Collection
    {
        return $this->equipmentQuery($modelClass, $districtId, $municipalityId)
            ->select('category', 'condition')
            ->selectRaw('count(*) as total')
            ->groupBy('category', 'condition')
            ->get()
            ->groupBy('category')
            ->map(fn (Collection $group, string $category): array => [
                'category' => $category,
                'conditions' => $group->pluck('total', 'condition'),
                'total' => $group->sum('total'),
            ])
            ->sortBy('category')
            ->values();
    }

    /**
     * @param  class-string<IctEquipment|OtherEquipment>  $modelClass
     * @return Collection<string, int>
     */
    private function statusBreakdown(string $modelClass, int $districtId, int $municipalityId): Collection
    {
        return $this->equipmentQuery($modelClass, $districtId, $municipalityId)
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
    }

    /**
     * @param  class-string<IctEquipment|OtherEquipment>  $modelClass
     */
    private function exportEquipmentSummary(string $modelClass, string $table, Request $request, string $filename): StreamedResponse
    {
        $rows = $this
            ->equipmentQuery($modelClass, $request->integer('district_id'), $request->integer('municipality_id'))
            ->join('schools', 'schools.id', '=', "{$table}.school_id")
            ->whereNull('schools.deleted_at')
            ->select('schools.school_id', 'schools.school_name', "{$table}.category", "{$table}.condition", "{$table}.status")
            ->selectRaw('count(*) as total')
            ->groupBy('schools.school_id', 'schools.school_name', "{$table}.category", "{$table}.condition", "{$table}.status")
            ->orderBy('schools.school_name')
            ->orderBy("{$table}.category")
            ->get()
            ->map(fn ($row): array => [
                $row->school_id,
                $row->school_name,
                $row->category,
                $row->condition,
                $row->status,
                $row->total,
            ]);

        return $this->streamCsv(
            $filename,
            ['School ID', 'School Name', 'Category', 'Condition', 'Status', 'Total'],
            $rows,
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function learnerEquipmentRatioByMunicipality(?int $schoolYearId, int $districtId, int $municipalityId): Collection
    {
        $learners = Enrollment::query()
            ->join('schools', 'schools.id', '=', 'enrollments.school_id')
            ->whereNull('schools.deleted_at')
            ->where('enrollments.school_year_id', $schoolYearId ?? 0)
            ->when($districtId > 0, fn (Builder $query) => $query->where('schools.district_id', $districtId))
            ->when($municipalityId > 0, fn (Builder $query) => $query->where('schools.municipality_id', $municipalityId))
            ->selectRaw('schools.municipality_id, COALESCE(SUM(enrollments.male_count + enrollments.female_count), 0) as total')
            ->groupBy('schools.municipality_id')
            ->pluck('total', 'schools.municipality_id');

        $ict = $this->countByMunicipality(IctEquipment::class, 'ict_equipment', $districtId, $municipalityId);
        $sme = $this->countByMunicipality(Sme::class, 'sme', $districtId, $municipalityId);
        $other = $this->countByMunicipality(OtherEquipment::class, 'other_equipment', $districtId, $municipalityId);

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
     * @return Collection<int, array<string, mixed>>
     */
    private function smeLabAdequacyByMunicipality(int $districtId, int $municipalityId): Collection
    {
        $schools = School::query()
            ->when($districtId > 0, fn (Builder $query) => $query->where('district_id', $districtId))
            ->when($municipalityId > 0, fn (Builder $query) => $query->where('municipality_id', $municipalityId))
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
     * @return Collection<int, array<string, mixed>>
     */
    private function equipmentFunctionalStatus(int $districtId, int $municipalityId): Collection
    {
        $buckets = [
            'ICT Equipment' => IctEquipment::class,
            'Science & Math' => Sme::class,
            'Other Equipment' => OtherEquipment::class,
        ];

        return collect($buckets)->map(function (string $modelClass, string $type) use ($districtId, $municipalityId): array {
            $rows = $this->equipmentQuery($modelClass, $districtId, $municipalityId)
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
     * @return array{districts: array<int, string>, resourceTypes: array<int, string>, values: array<int, array<int, int>>}
     */
    private function districtResourceHeatmap(int $districtId, int $municipalityId): array
    {
        $districts = District::query()
            ->when($districtId > 0, fn (Builder $query) => $query->whereKey($districtId))
            ->when($municipalityId > 0, fn (Builder $query) => $query->whereHas(
                'schools',
                fn (Builder $schoolQuery) => $schoolQuery->where('municipality_id', $municipalityId),
            ))
            ->orderBy('name')
            ->get();

        $printed = $this->sumByDistrict(LearningResource::class, 'learning_resources', $districtId, $municipalityId, 'learning_resources.quantity_delivered');
        $ict = $this->countByDistrict(IctEquipment::class, 'ict_equipment', $districtId, $municipalityId);
        $sme = $this->countByDistrict(Sme::class, 'sme', $districtId, $municipalityId);
        $other = $this->countByDistrict(OtherEquipment::class, 'other_equipment', $districtId, $municipalityId);

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

    private function countByMunicipality(string $modelClass, string $table, int $districtId, int $municipalityId): Collection
    {
        return $modelClass::query()
            ->join('schools', 'schools.id', '=', "{$table}.school_id")
            ->whereNull('schools.deleted_at')
            ->when($districtId > 0, fn (Builder $query) => $query->where('schools.district_id', $districtId))
            ->when($municipalityId > 0, fn (Builder $query) => $query->where('schools.municipality_id', $municipalityId))
            ->selectRaw('schools.municipality_id, COUNT(*) as total')
            ->groupBy('schools.municipality_id')
            ->pluck('total', 'schools.municipality_id');
    }

    private function countByDistrict(string $modelClass, string $table, int $districtId, int $municipalityId): Collection
    {
        return $modelClass::query()
            ->join('schools', 'schools.id', '=', "{$table}.school_id")
            ->whereNull('schools.deleted_at')
            ->when($districtId > 0, fn (Builder $query) => $query->where('schools.district_id', $districtId))
            ->when($municipalityId > 0, fn (Builder $query) => $query->where('schools.municipality_id', $municipalityId))
            ->selectRaw('schools.district_id, COUNT(*) as total')
            ->groupBy('schools.district_id')
            ->pluck('total', 'schools.district_id');
    }

    private function sumByDistrict(string $modelClass, string $table, int $districtId, int $municipalityId, string $quantityColumn): Collection
    {
        return $modelClass::query()
            ->join('schools', 'schools.id', '=', "{$table}.school_id")
            ->whereNull('schools.deleted_at')
            ->when($districtId > 0, fn (Builder $query) => $query->where('schools.district_id', $districtId))
            ->when($municipalityId > 0, fn (Builder $query) => $query->where('schools.municipality_id', $municipalityId))
            ->selectRaw("schools.district_id, COALESCE(SUM({$quantityColumn}), 0) as total")
            ->groupBy('schools.district_id')
            ->pluck('total', 'schools.district_id');
    }

    /**
     * @param  class-string<IctEquipment|OtherEquipment|Sme>  $modelClass
     */
    private function equipmentQuery(string $modelClass, int $districtId, int $municipalityId): Builder
    {
        return $modelClass::query()
            ->when($districtId > 0 || $municipalityId > 0, fn (Builder $query) => $query->whereHas(
                'school',
                fn (Builder $schoolQuery) => $schoolQuery
                    ->when($districtId > 0, fn (Builder $q) => $q->where('district_id', $districtId))
                    ->when($municipalityId > 0, fn (Builder $q) => $q->where('municipality_id', $municipalityId)),
            ));
    }

    private function smeQuery(int $districtId, int $municipalityId): Builder
    {
        return Sme::query()
            ->when($districtId > 0 || $municipalityId > 0, fn (Builder $query) => $query->whereHas(
                'school',
                fn (Builder $schoolQuery) => $schoolQuery
                    ->when($districtId > 0, fn (Builder $q) => $q->where('district_id', $districtId))
                    ->when($municipalityId > 0, fn (Builder $q) => $q->where('municipality_id', $municipalityId)),
            ));
    }

    private function resolveSchoolYearId(Request $request): ?int
    {
        $requested = $request->integer('school_year_id');

        if ($requested > 0 && SchoolYear::query()->whereKey($requested)->exists()) {
            return $requested;
        }

        return SchoolYear::active()?->id;
    }

    /**
     * @param  list<string>  $header
     * @param  Collection<int, array<int, mixed>>  $rows
     */
    private function streamCsv(string $filename, array $header, Collection $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, $header);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
