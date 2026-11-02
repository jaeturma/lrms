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

    private const SECTIONS_WITH_EDITABLE_REFERENCE = [
        ValidLrVisitItem::SECTION_LEARNERS_WITH_DISABILITIES,
    ];

    public function __construct(private ValidLrSchoolProfileService $profileService) {}

    /**
     * Starts a visit with a snapshot of the school's current reference data,
     * so later re-imports never change what was validated.
     */
    public function start(ValidLrSchool $validLrSchool, User $user): ValidLrVisit
    {
        $profile = $this->profileService->build($validLrSchool);

        return DB::transaction(function () use ($validLrSchool, $user, $profile): ValidLrVisit {
            $visit = $validLrSchool->visits()->create([
                'user_id' => $user->id,
                'status' => 'draft',
                'visited_at' => now(),
                'prepared_by' => $user->name,
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
     * @param  array<string, mixed>  $data  validated UpdateValidLrVisitRequest data
     */
    public function update(ValidLrVisit $visit, array $data): ValidLrVisit
    {
        DB::transaction(function () use ($visit, $data): void {
            $visit->update(collect($data)->except('items')->all());

            $items = $visit->items()->get()->keyBy('id');

            foreach ($data['items'] ?? [] as $input) {
                $item = $items->get((int) $input['id']);

                if (! $item) {
                    continue;
                }

                $attributes = collect($input)->only(self::EDITABLE_ITEM_FIELDS)->all();

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
        });

        return $visit->refresh();
    }
}
