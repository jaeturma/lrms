<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\LearningResource;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\ValidLrSchool;
use App\Models\ValidLrSmeItem;
use App\Models\ValidLrTitle;
use App\Models\ValidLrTvlItem;
use App\Support\ValidLrTemplate;

/**
 * Builds what the VALID-LR monitoring tool shows when a School ID is
 * entered: allocated LRs with the region's title/publisher, LRMIS-encoded
 * totals, SME packages for the key stages delivered, and TVL packages for
 * the school's specializations. Where the school also exists in LRMS, its
 * recorded deliveries and enrolment are added alongside.
 */
class ValidLrSchoolProfileService
{
    /**
     * @return array{
     *     learning_resources: array<int, array{level: string, group_label: string, label: string, title: ?string, publisher: ?string, allocated: ?int, lrmis: ?int, lrms: ?int, enrolment: ?int}>,
     *     sme: array{timeline: array<string, array<int, int>>, central_office: array<int, array<string, mixed>>, downloaded: array<int, array<string, mixed>>},
     *     tvl: array{programs: array<int, array{specialization: string, delivery_years: array<int, int>, status: ?string}>, items: array<int, array<string, mixed>>}
     * }
     */
    public function build(ValidLrSchool $validLrSchool): array
    {
        return [
            'learning_resources' => $this->learningResources($validLrSchool),
            'sme' => $this->sme($validLrSchool),
            'tvl' => $this->tvl($validLrSchool),
        ];
    }

    /**
     * @return array<int, array{level: string, group_label: string, label: string, title: ?string, publisher: ?string, allocated: ?int, lrmis: ?int, lrms: ?int, enrolment: ?int}>
     */
    private function learningResources(ValidLrSchool $validLrSchool): array
    {
        $allocations = $validLrSchool->allocations()->get()
            ->mapWithKeys(fn ($allocation): array => [$this->key($allocation->grade_level, $allocation->learning_area) => $allocation->quantity]);
        $lrmisCounts = $validLrSchool->lrmisCounts()->get()
            ->mapWithKeys(fn ($count): array => [$this->key($count->grade_level, $count->learning_area) => $count->total]);
        $titles = ValidLrTitle::query()
            ->where('region', (string) $validLrSchool->region)
            ->get()
            ->keyBy(fn (ValidLrTitle $title): string => $this->key($title->grade_level, $title->learning_area));

        $lrmsSchool = $validLrSchool->school;
        $lrmsQuantities = $lrmsSchool ? $this->lrmsQuantities($lrmsSchool) : [];
        $enrolment = $lrmsSchool ? $this->enrolment($lrmsSchool) : [];

        // Elementary allocation columns hold division-level totals for every
        // school, so allocations only decide the levels when the curricular
        // offering is unknown.
        $offeringKnown = filled($validLrSchool->curricular_offering);
        $levels = [];

        if ($offeringKnown ? $validLrSchool->offersElementary() : $this->hasAllocationFor($allocations->keys()->all(), ValidLrTemplate::ELEMENTARY)) {
            $levels['elementary'] = ValidLrTemplate::ELEMENTARY;
        }

        if ($offeringKnown ? $validLrSchool->offersSecondary() : $this->hasAllocationFor($allocations->keys()->all(), ValidLrTemplate::SECONDARY)) {
            $levels['secondary'] = ValidLrTemplate::SECONDARY;
        }

        $rows = [];

        foreach ($levels as $level => $groups) {
            foreach ($groups as $groupLabel => $learningAreas) {
                foreach ($learningAreas as $learningArea) {
                    $key = $this->key($groupLabel, $learningArea);
                    $title = $titles->get($key);

                    $rows[] = [
                        'level' => $level,
                        'group_label' => $groupLabel,
                        'label' => $learningArea,
                        'title' => $title?->title ?? ($groupLabel === ValidLrTemplate::NON_MATATAG_GROUP ? $learningArea : null),
                        'publisher' => $title?->publisher,
                        'allocated' => $allocations->get($key),
                        'lrmis' => $this->sumForGrades($lrmisCounts->all(), ValidLrTemplate::lrmisGradesFor($groupLabel), $learningArea),
                        'lrms' => $this->sumForGrades($lrmsQuantities, ValidLrTemplate::lrmisGradesFor($groupLabel), $learningArea),
                        'enrolment' => $this->enrolmentFor($enrolment, $groupLabel),
                    ];
                }
            }
        }

        return $rows;
    }

    /**
     * @return array{timeline: array<string, array<int, int>>, central_office: array<int, array<string, mixed>>, downloaded: array<int, array<string, mixed>>}
     */
    private function sme(ValidLrSchool $validLrSchool): array
    {
        $timeline = [];

        foreach (ValidLrSmeItem::KEY_STAGES as $keyStage) {
            $timeline[$keyStage] = [];
        }

        foreach ($validLrSchool->sme_deliveries ?? [] as $delivery) {
            $timeline[$delivery['key_stage']][] = (int) $delivery['year'];
        }

        $timeline = array_map(function (array $years): array {
            $years = array_values(array_unique($years));
            sort($years);

            return $years;
        }, $timeline);

        $deliveredKeyStages = array_keys(array_filter($timeline));
        $items = $deliveredKeyStages === []
            ? collect()
            : ValidLrSmeItem::query()
                ->whereIn('key_stage', $deliveredKeyStages)
                ->where('quantity', '>', 0)
                ->orderBy('id')
                ->get();

        $map = fn (ValidLrSmeItem $item): array => [
            'group_label' => $item->group_label,
            'item_name' => $item->item_name,
            'unit' => $item->unit,
            'key_stage' => $item->key_stage,
            'quantity' => $item->quantity,
            'years_delivered' => $timeline[$item->key_stage] ?? [],
        ];

        return [
            'timeline' => $timeline,
            'central_office' => $items->where('source', ValidLrSmeItem::SOURCE_CENTRAL_OFFICE)->map($map)->values()->all(),
            'downloaded' => $items->where('source', ValidLrSmeItem::SOURCE_DOWNLOADED)->map($map)->values()->all(),
        ];
    }

    /**
     * @return array{programs: array<int, array{specialization: string, delivery_years: array<int, int>, status: ?string}>, items: array<int, array<string, mixed>>}
     */
    private function tvl(ValidLrSchool $validLrSchool): array
    {
        $programs = $validLrSchool->tvlPrograms()->orderBy('specialization')->get();
        $yearsBySpecialization = $programs->mapWithKeys(fn ($program): array => [$program->specialization => $program->delivery_years ?? []]);

        $items = $programs->isEmpty()
            ? collect()
            : ValidLrTvlItem::query()
                ->whereIn('specialization', $programs->pluck('specialization'))
                ->orderBy('specialization')
                ->orderBy('id')
                ->get();

        return [
            'programs' => $programs->map(fn ($program): array => [
                'specialization' => $program->specialization,
                'delivery_years' => $program->delivery_years ?? [],
                'status' => $program->status,
            ])->values()->all(),
            'items' => $items->map(fn (ValidLrTvlItem $item): array => [
                'specialization' => $item->specialization,
                'item_name' => $item->item_name,
                'procurement_source' => $item->procurement_source,
                'quantity' => $item->quantity,
                'years_delivered' => $yearsBySpecialization->get($item->specialization, []),
            ])->values()->all(),
        ];
    }

    /**
     * Total quantity delivered per grade/learning area as recorded in LRMS.
     *
     * @return array<string, int>
     */
    private function lrmsQuantities(School $school): array
    {
        return LearningResource::query()
            ->where('learning_resources.school_id', $school->id)
            ->join('grade_levels', 'grade_levels.id', '=', 'learning_resources.grade_level_id')
            ->whereNotNull('learning_resources.subject')
            ->selectRaw('grade_levels.name as grade_name, learning_resources.subject as subject, sum(learning_resources.quantity_delivered) as total')
            ->groupBy('grade_levels.name', 'learning_resources.subject')
            ->get()
            ->reduce(function (array $carry, $row): array {
                $key = $this->key((string) $row->grade_name, ValidLrTemplate::normalizeLearningArea((string) $row->subject));
                $carry[$key] = ($carry[$key] ?? 0) + (int) $row->total;

                return $carry;
            }, []);
    }

    /**
     * Learners per LRMS grade level name for the active school year.
     *
     * @return array<string, int>
     */
    private function enrolment(School $school): array
    {
        $schoolYear = SchoolYear::active();

        if (! $schoolYear) {
            return [];
        }

        return Enrollment::query()
            ->with('gradeLevel:id,name')
            ->where('school_id', $school->id)
            ->where('school_year_id', $schoolYear->id)
            ->get()
            ->groupBy(fn (Enrollment $enrollment): string => (string) $enrollment->gradeLevel?->name)
            ->map(fn ($enrollments): int => $enrollments->sum(fn (Enrollment $enrollment): int => $enrollment->totalLearners()))
            ->all();
    }

    /**
     * @param  array<string, int>  $enrolment
     */
    private function enrolmentFor(array $enrolment, string $groupLabel): ?int
    {
        $grades = ValidLrTemplate::enrolmentGradesFor($groupLabel);
        $values = array_filter(array_map(fn (string $grade): ?int => $enrolment[$grade] ?? null, $grades), fn (?int $value): bool => $value !== null);

        return $values === [] ? null : array_sum($values);
    }

    /**
     * @param  array<string, int>  $totals
     * @param  array<int, string>  $grades
     */
    private function sumForGrades(array $totals, array $grades, string $learningArea): ?int
    {
        $values = [];

        foreach ($grades as $grade) {
            $key = $this->key($grade, $learningArea);

            if (array_key_exists($key, $totals)) {
                $values[] = (int) $totals[$key];
            }
        }

        return $values === [] ? null : array_sum($values);
    }

    /**
     * @param  array<int, string>  $allocationKeys
     * @param  array<string, array<int, string>>  $groups
     */
    private function hasAllocationFor(array $allocationKeys, array $groups): bool
    {
        foreach ($groups as $groupLabel => $learningAreas) {
            foreach ($learningAreas as $learningArea) {
                if (in_array($this->key($groupLabel, $learningArea), $allocationKeys, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function key(string $gradeLevel, string $learningArea): string
    {
        return mb_strtolower(trim($gradeLevel)).'|'.mb_strtolower(trim($learningArea));
    }
}
