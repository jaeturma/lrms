<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\ValidLrVisit;
use App\Models\ValidLrVisitItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What a school must finish before the monitoring team validates: its
 * profile (school head, email and the validation personnel), current-year
 * enrolment, and its own count of every LR, SME and TVL item.
 */
class ValidLrPreparationService
{
    /**
     * @return array{
     *     in_lrms: bool,
     *     profile_complete: bool,
     *     missing_profile: array<int, string>,
     *     enrolment_complete: bool,
     *     school_year: ?string,
     *     items_total: int,
     *     items_counted: int,
     *     counts_complete: bool,
     *     ready: bool
     * }
     */
    public function checklist(ValidLrVisit $visit): array
    {
        $school = $visit->validLrSchool->school;
        $progress = $this->progress(collect([$visit->id]))->get($visit->id, ['items_total' => 0, 'school_counted' => 0, 'validated' => 0, 'mismatched' => 0]);
        $schoolYear = SchoolYear::active();

        $missingProfile = $school ? $this->missingProfile($school) : array_merge(array_values(ValidLrVisit::PERSONNEL), ['Email']);
        $enrolmentComplete = $school !== null && $schoolYear !== null && $this->hasEnrolment($school, $schoolYear);
        $countsComplete = $progress['items_total'] > 0 && $progress['school_counted'] === $progress['items_total'];

        return [
            'in_lrms' => $school !== null,
            'profile_complete' => $missingProfile === [],
            'missing_profile' => $missingProfile,
            'enrolment_complete' => $enrolmentComplete,
            'school_year' => $schoolYear?->name,
            'items_total' => $progress['items_total'],
            'items_counted' => $progress['school_counted'],
            'counts_complete' => $countsComplete,
            'ready' => $missingProfile === [] && $enrolmentComplete && $countsComplete,
        ];
    }

    /**
     * Counted-item progress per visit, for the countable sections only.
     *
     * @param  Collection<int, int>  $visitIds
     * @return Collection<int, array{items_total: int, school_counted: int, validated: int, mismatched: int}>
     */
    public function progress(Collection $visitIds): Collection
    {
        if ($visitIds->isEmpty()) {
            return collect();
        }

        return ValidLrVisitItem::query()
            ->whereIn('valid_lr_visit_id', $visitIds)
            ->whereIn('section', ValidLrVisit::COUNTED_SECTIONS)
            ->groupBy('valid_lr_visit_id')
            ->select('valid_lr_visit_id')
            ->selectRaw('count(*) as items_total')
            ->selectRaw('sum(case when school_quantity is not null then 1 else 0 end) as school_counted')
            ->selectRaw('sum(case when actual_quantity is not null then 1 else 0 end) as validated')
            ->selectRaw('sum(case when actual_quantity is not null and school_quantity is not null and actual_quantity <> school_quantity then 1 else 0 end) as mismatched')
            ->get()
            ->mapWithKeys(fn ($row): array => [(int) $row->valid_lr_visit_id => [
                'items_total' => (int) $row->items_total,
                'school_counted' => (int) $row->school_counted,
                'validated' => (int) $row->validated,
                'mismatched' => (int) $row->mismatched,
            ]]);
    }

    /**
     * @return array<int, string>
     */
    public function missingProfile(School $school): array
    {
        $missing = collect(ValidLrVisit::PERSONNEL)
            ->filter(fn (string $label, string $field): bool => blank($school->{$field}))
            ->values()
            ->all();

        if (blank($school->email)) {
            $missing[] = 'Email';
        }

        return $missing;
    }

    /**
     * LRMS school ids that have enrolment encoded for the active school year.
     *
     * @param  Collection<int, int>  $schoolIds
     * @return Collection<int, int>
     */
    public function schoolsWithEnrolment(Collection $schoolIds): Collection
    {
        $schoolYear = SchoolYear::active();

        if (! $schoolYear || $schoolIds->isEmpty()) {
            return collect();
        }

        return Enrollment::query()
            ->whereIn('school_id', $schoolIds)
            ->where('school_year_id', $schoolYear->id)
            ->groupBy('school_id')
            ->havingRaw('sum(male_count + female_count) > 0')
            ->pluck('school_id')
            ->map(fn ($id): int => (int) $id);
    }

    private function hasEnrolment(School $school, SchoolYear $schoolYear): bool
    {
        return (int) DB::table('enrollments')
            ->where('school_id', $school->id)
            ->where('school_year_id', $schoolYear->id)
            ->sum(DB::raw('male_count + female_count')) > 0;
    }
}
