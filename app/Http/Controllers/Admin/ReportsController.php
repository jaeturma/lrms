<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Equipment;
use App\Models\Municipality;
use App\Models\School;
use App\Models\SchoolYear;
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
            'equipmentByCategory' => $this->equipmentByCategory($districtId, $municipalityId),
            'equipmentByStatus' => $this->equipmentByStatus($districtId, $municipalityId),
            'equipmentConditions' => Equipment::CONDITIONS,
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

    public function exportEquipment(Request $request): StreamedResponse
    {
        $rows = $this
            ->equipmentQuery($request->integer('district_id'), $request->integer('municipality_id'))
            ->join('schools', 'schools.id', '=', 'equipment.school_id')
            ->whereNull('schools.deleted_at')
            ->select('schools.school_id', 'schools.school_name', 'equipment.category', 'equipment.condition', 'equipment.status')
            ->selectRaw('count(*) as total')
            ->groupBy('schools.school_id', 'schools.school_name', 'equipment.category', 'equipment.condition', 'equipment.status')
            ->orderBy('schools.school_name')
            ->orderBy('equipment.category')
            ->get()
            ->map(fn (Equipment $row): array => [
                $row->school_id,
                $row->school_name,
                $row->category,
                $row->condition,
                $row->status,
                $row->total,
            ]);

        return $this->streamCsv(
            'equipment-summary.csv',
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
     * @return Collection<int, array<string, mixed>>
     */
    private function equipmentByCategory(int $districtId, int $municipalityId): Collection
    {
        return $this->equipmentQuery($districtId, $municipalityId)
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
     * @return Collection<string, int>
     */
    private function equipmentByStatus(int $districtId, int $municipalityId): Collection
    {
        return $this->equipmentQuery($districtId, $municipalityId)
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
    }

    private function equipmentQuery(int $districtId, int $municipalityId): Builder
    {
        return Equipment::query()
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
