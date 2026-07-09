<?php

namespace App\Services;

use App\Models\School;
use App\Models\Sme;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SmeService
{
    /**
     * Fields whose changes are recorded as dedicated movement entries.
     *
     * @var array<string, string>
     */
    private const TRACKED_FIELDS = [
        'status' => 'status_change',
        'condition' => 'condition_change',
        'assigned_personnel' => 'reassigned',
        'current_location' => 'relocated',
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(School $school, array $data, ?User $user = null): Sme
    {
        return DB::transaction(function () use ($school, $data, $user): Sme {
            $sme = $school->sme()->create($data);

            if (blank($sme->item_code)) {
                $sme->item_code = sprintf('SME-%s-%05d', now()->year, $sme->id);
            }

            if (blank($sme->qr_code)) {
                $sme->qr_code = $sme->item_code;
            }

            $sme->save();

            $sme->movements()->create([
                'school_id' => $school->id,
                'user_id' => $user?->id,
                'type' => 'created',
                'to_value' => $sme->status,
                'notes' => 'SME item registered',
            ]);

            return $sme;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Sme $sme, array $data, ?User $user = null): Sme
    {
        return DB::transaction(function () use ($sme, $data, $user): Sme {
            $original = $sme->only(array_keys(self::TRACKED_FIELDS));

            $sme->fill($data);

            if (blank($sme->item_code)) {
                $sme->item_code = sprintf('SME-%s-%05d', now()->year, $sme->id);
            }

            $otherFieldsChanged = collect($sme->getDirty())
                ->keys()
                ->diff(array_keys(self::TRACKED_FIELDS))
                ->isNotEmpty();

            $sme->save();

            foreach (self::TRACKED_FIELDS as $field => $movementType) {
                if ((string) $original[$field] === (string) $sme->{$field}) {
                    continue;
                }

                $sme->movements()->create([
                    'school_id' => $sme->school_id,
                    'user_id' => $user?->id,
                    'type' => $movementType,
                    'from_value' => $original[$field],
                    'to_value' => $sme->{$field},
                    'notes' => $data['movement_notes'] ?? null,
                ]);
            }

            if ($otherFieldsChanged) {
                $sme->movements()->create([
                    'school_id' => $sme->school_id,
                    'user_id' => $user?->id,
                    'type' => 'updated',
                    'notes' => 'SME item details updated',
                ]);
            }

            return $sme;
        });
    }

    public function delete(Sme $sme, ?User $user = null): void
    {
        DB::transaction(function () use ($sme, $user): void {
            $sme->movements()->create([
                'school_id' => $sme->school_id,
                'user_id' => $user?->id,
                'type' => 'deleted',
                'from_value' => $sme->status,
                'notes' => 'SME item record removed',
            ]);

            $sme->delete();
        });
    }
}
