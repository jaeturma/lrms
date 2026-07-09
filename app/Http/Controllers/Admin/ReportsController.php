<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\IctEquipment;
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
     * @param  class-string<IctEquipment|OtherEquipment>  $modelClass
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
