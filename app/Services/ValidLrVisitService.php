<?php

namespace App\Services;

use App\Models\User;
use App\Models\ValidLrSchool;
use App\Models\ValidLrVisit;
use App\Models\ValidLrVisitItem;
use App\Support\ValidLrTemplate;
use Illuminate\Support\Facades\DB;

class ValidLrVisitService
{
    /**
     * Fields a monitor may change on a visit item. `reference_quantity` is
     * only editable for sections with no imported reference value.
     */
    private const EDITABLE_ITEM_FIELDS = [
        'distributed_quantity',
        'actual_quantity',
        'enrolment',
        'year_received',
        'mov',
        'remarks_repairs',
        'remarks_missing',
        'remarks_others',
    ];

    /**
     * Fields a school may change while preparing for monitoring. The
     * validated `actual_quantity` belongs to the monitoring team.
     */
    private const SCHOOL_EDITABLE_ITEM_FIELDS = [
        'school_quantity',
        'distributed_quantity',
        'enrolment',
        'year_received',
        'mov',
        'remarks_repairs',
        'remarks_missing',
        'remarks_others',
    ];

    private const SECTIONS_WITH_EDITABLE_REFERENCE = [
        ValidLrVisitItem::SECTION_LEARNERS_WITH_DISABILITIES,
    ];

    public function __construct(private ValidLrSchoolProfileService $profileService) {}

    /**
     * Starts a visit with a snapshot of the school's current reference data,
     * so later re-imports never change what was validated.
     */
    public function start(ValidLrSchool $validLrSchool, ?User $user, array $assignment = []): ValidLrVisit
    {
        $profile = $this->profileService->build($validLrSchool);

        return DB::transaction(function () use ($validLrSchool, $user, $profile, $assignment): ValidLrVisit {
            $visit = $validLrSchool->visits()->create([
                'user_id' => $user?->id,
                'status' => 'draft',
                'visited_at' => $assignment['scheduled_at'] ?? now(),
                'team_leader_id' => $assignment['team_leader_id'] ?? null,
                'scheduled_at' => $assignment['scheduled_at'] ?? null,
                'monitoring_team' => $assignment['monitoring_team'] ?? null,
                'school_representative' => $validLrSchool->school?->school_head,
                'prepared_by' => $user?->name,
                'indicators' => collect(ValidLrVisit::INDICATORS)
                    ->map(fn (): array => ['answer' => null, 'remarks' => null])
                    ->all(),
            ]);

            $sortOrder = 0;
            $items = [];

            foreach ($profile['learning_resources'] as $row) {
                $items[] = [
                    'section' => ValidLrVisitItem::SECTION_LEARNING_RESOURCES,
                    'group_label' => $row['group_label'],
                    'label' => $row['label'],
                    'title' => $row['title'],
                    'publisher' => $row['publisher'],
                    'reference_quantity' => $row['allocated'],
                    'lrmis_quantity' => $row['lrmis'],
                    'lrms_quantity' => $row['lrms'],
                    'enrolment' => $row['enrolment'],
                    'extra' => ['level' => $row['level']],
                ];
            }

            foreach (['central_office' => ValidLrVisitItem::SECTION_SME_CENTRAL_OFFICE, 'downloaded' => ValidLrVisitItem::SECTION_SME_DOWNLOADED] as $source => $section) {
                foreach ($profile['sme'][$source] as $row) {
                    $items[] = [
                        'section' => $section,
                        'group_label' => $row['group_label'],
                        'label' => $row['item_name'],
                        'unit' => $row['unit'],
                        'reference_quantity' => $row['quantity'],
                        'year_received' => implode(', ', $row['years_delivered']) ?: null,
                        'extra' => ['key_stage' => $row['key_stage']],
                    ];
                }
            }

            foreach ($profile['tvl']['items'] as $row) {
                $items[] = [
                    'section' => ValidLrVisitItem::SECTION_TVL,
                    'group_label' => $row['specialization'],
                    'label' => $row['item_name'],
                    'procurement_source' => $row['procurement_source'],
                    'reference_quantity' => $row['quantity'],
                    'year_received' => implode(', ', $row['years_delivered']) ?: null,
                ];
            }

            foreach (ValidLrTemplate::LEARNERS_WITH_DISABILITIES as $label) {
                $items[] = [
                    'section' => ValidLrVisitItem::SECTION_LEARNERS_WITH_DISABILITIES,
                    'label' => $label,
                ];
            }

            foreach (ValidLrTemplate::ARAL as $groupLabel => $labels) {
                foreach ($labels as $label) {
                    $items[] = [
                        'section' => ValidLrVisitItem::SECTION_ARAL,
                        'group_label' => $groupLabel,
                        'label' => $label,
                        'extra' => array_fill_keys(array_keys(ValidLrVisitItem::ARAL_PROGRAMS), null),
                    ];
                }
            }

            foreach ($items as $item) {
                $visit->items()->create($item + ['sort_order' => $sortOrder++]);
            }

            return $visit;
        });
    }

    /**
     * The school's open visit, started on first use so the school can begin
     * counting before the monitoring team arrives.
     */
    public function openVisitFor(ValidLrSchool $validLrSchool, bool $createIfMissing = false): ?ValidLrVisit
    {
        $visit = $validLrSchool->visits()->where('status', '!=', 'completed')->latest('id')->first();

        if (! $visit && $createIfMissing && ! $validLrSchool->visits()->exists()) {
            $visit = $this->start($validLrSchool, null);
        }

        return $visit;
    }

    /**
     * Monitoring team save. The first save marks validation as started.
     *
     * @param  array<string, mixed>  $data  validated UpdateValidLrVisitRequest data
     */
    public function update(ValidLrVisit $visit, array $data): ValidLrVisit
    {
        DB::transaction(function () use ($visit, $data): void {
            $visit->fill(collect($data)->except('items')->all());
            $visit->validation_started_at ??= now();
            $visit->save();

            $this->updateItems($visit, $data['items'] ?? [], self::EDITABLE_ITEM_FIELDS);
        });

        return $visit->refresh();
    }

    /**
     * School save while preparing for (or during) monitoring.
     *
     * @param  array<string, mixed>  $data  validated UpdateValidLrSchoolCountRequest data
     */
    public function updateBySchool(ValidLrVisit $visit, array $data): ValidLrVisit
    {
        DB::transaction(function () use ($visit, $data): void {
            if (array_key_exists('school_representative', $data)) {
                $visit->update(['school_representative' => $data['school_representative']]);
            }

            $this->updateItems($visit, $data['items'] ?? [], self::SCHOOL_EDITABLE_ITEM_FIELDS);
            $visit->touch();
        });

        return $visit->refresh();
    }

    public function submitBySchool(ValidLrVisit $visit, User $user): ValidLrVisit
    {
        $visit->update([
            'school_submitted_at' => now(),
            'school_submitted_by' => $user->id,
        ]);

        return $visit;
    }

    /**
     * Corrects LRMIS-encoded counts on a visit's learning resource rows,
     * keeping the imported value and who changed it. Rows covering a single
     * grade also update the school's reference LRMIS count.
     *
     * @param  array<int, array{id: int, lrmis_quantity: ?int}>  $inputs
     */
    public function adjustLrmis(ValidLrVisit $visit, array $inputs, User $user): int
    {
        $items = $visit->items()->where('section', ValidLrVisitItem::SECTION_LEARNING_RESOURCES)->get()->keyBy('id');
        $changed = 0;

        DB::transaction(function () use ($visit, $inputs, $user, $items, &$changed): void {
            foreach ($inputs as $input) {
                $item = $items->get((int) $input['id']);
                $quantity = $input['lrmis_quantity'] === null ? null : (int) $input['lrmis_quantity'];

                if (! $item || $item->lrmis_quantity === $quantity) {
                    continue;
                }

                $item->forceFill([
                    'lrmis_original_quantity' => $item->lrmis_adjusted_at ? $item->lrmis_original_quantity : $item->lrmis_quantity,
                    'lrmis_quantity' => $quantity,
                    'lrmis_adjusted_by' => $user->id,
                    'lrmis_adjusted_at' => now(),
                ])->save();
                $changed++;

                $grades = ValidLrTemplate::lrmisGradesFor((string) $item->group_label);

                if (count($grades) === 1 && $quantity !== null) {
                    $visit->validLrSchool->lrmisCounts()->updateOrCreate(
                        ['grade_level' => $grades[0], 'learning_area' => $item->label],
                        ['total' => $quantity],
                    );
                }
            }
        });

        return $changed;
    }

    /**
     * @param  array<int, array<string, mixed>>  $inputs
     * @param  array<int, string>  $editableFields
     */
    private function updateItems(ValidLrVisit $visit, array $inputs, array $editableFields): void
    {
        $items = $visit->items()->get()->keyBy('id');

        foreach ($inputs as $input) {
            $item = $items->get((int) $input['id']);

            if (! $item) {
                continue;
            }

            $attributes = collect($input)->only($editableFields)->all();

            if (in_array($item->section, self::SECTIONS_WITH_EDITABLE_REFERENCE, true) && array_key_exists('reference_quantity', $input)) {
                $attributes['reference_quantity'] = $input['reference_quantity'];
            }

            if ($item->section === ValidLrVisitItem::SECTION_ARAL && is_array($input['extra'] ?? null)) {
                $attributes['extra'] = collect($input['extra'])
                    ->only(array_keys(ValidLrVisitItem::ARAL_PROGRAMS))
                    ->map(fn (mixed $value): ?int => $value === null || $value === '' ? null : (int) $value)
                    ->all();
            }

            $item->fill($attributes);

            if ($item->isDirty()) {
                $item->save();
            }
        }
    }
}
