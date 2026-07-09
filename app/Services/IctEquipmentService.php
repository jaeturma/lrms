<?php

namespace App\Services;

use App\Models\IctEquipment;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class IctEquipmentService
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
    public function create(School $school, array $data, ?User $user = null): IctEquipment
    {
        return DB::transaction(function () use ($school, $data, $user): IctEquipment {
            $equipment = $school->ictEquipment()->create($data);

            if (blank($equipment->item_code)) {
                $equipment->item_code = sprintf('ICT-%s-%05d', now()->year, $equipment->id);
            }

            if (blank($equipment->qr_code)) {
                $equipment->qr_code = $equipment->item_code;
            }

            $equipment->save();

            $equipment->movements()->create([
                'school_id' => $school->id,
                'user_id' => $user?->id,
                'type' => 'created',
                'to_value' => $equipment->status,
                'notes' => 'Equipment registered',
            ]);

            return $equipment;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(IctEquipment $equipment, array $data, ?User $user = null): IctEquipment
    {
        return DB::transaction(function () use ($equipment, $data, $user): IctEquipment {
            $original = $equipment->only(array_keys(self::TRACKED_FIELDS));

            $equipment->fill($data);

            if (blank($equipment->item_code)) {
                $equipment->item_code = sprintf('ICT-%s-%05d', now()->year, $equipment->id);
            }

            $otherFieldsChanged = collect($equipment->getDirty())
                ->keys()
                ->diff(array_keys(self::TRACKED_FIELDS))
                ->isNotEmpty();

            $equipment->save();

            foreach (self::TRACKED_FIELDS as $field => $movementType) {
                if ((string) $original[$field] === (string) $equipment->{$field}) {
                    continue;
                }

                $equipment->movements()->create([
                    'school_id' => $equipment->school_id,
                    'user_id' => $user?->id,
                    'type' => $movementType,
                    'from_value' => $original[$field],
                    'to_value' => $equipment->{$field},
                    'notes' => $data['movement_notes'] ?? null,
                ]);
            }

            if ($otherFieldsChanged) {
                $equipment->movements()->create([
                    'school_id' => $equipment->school_id,
                    'user_id' => $user?->id,
                    'type' => 'updated',
                    'notes' => 'Equipment details updated',
                ]);
            }

            return $equipment;
        });
    }

    public function delete(IctEquipment $equipment, ?User $user = null): void
    {
        DB::transaction(function () use ($equipment, $user): void {
            $equipment->movements()->create([
                'school_id' => $equipment->school_id,
                'user_id' => $user?->id,
                'type' => 'deleted',
                'from_value' => $equipment->status,
                'notes' => 'Equipment record removed',
            ]);

            $equipment->delete();
        });
    }
}
