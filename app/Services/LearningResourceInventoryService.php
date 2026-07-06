<?php

namespace App\Services;

use App\Models\LearningResource;
use App\Models\LearningResourceInventory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LearningResourceInventoryService
{
    /**
     * Allowed source statuses per movement type. The target status is fixed
     * by the movement type; "returned" and the sources requiring a choice
     * accept a from_status picked by the encoder.
     *
     * @var array<string, array{sources: array<int, string>, target: string}>
     */
    private const TRANSITIONS = [
        'issued' => ['sources' => ['available'], 'target' => 'issued'],
        'borrowed' => ['sources' => ['available'], 'target' => 'borrowed'],
        'returned' => ['sources' => ['issued', 'borrowed'], 'target' => 'available'],
        'damaged' => ['sources' => ['available', 'issued', 'borrowed'], 'target' => 'damaged'],
        'lost' => ['sources' => ['available', 'issued', 'borrowed'], 'target' => 'lost'],
        'condemned' => ['sources' => ['damaged', 'available'], 'target' => 'condemned'],
    ];

    /**
     * Create the inventory for a newly encoded learning resource and record
     * the opening movements.
     */
    public function initialize(LearningResource $resource, ?User $user = null): LearningResourceInventory
    {
        $delivered = (int) $resource->quantity_delivered;
        $damaged = min((int) $resource->quantity_with_issue_defect, $delivered);

        $inventory = $resource->inventory()->create([
            'available' => $delivered - $damaged,
            'damaged' => $damaged,
        ]);

        $this->recordMovementRow($resource, $user, 'received', $delivered, null, 'available', 'Initial encoding');

        if ($damaged > 0) {
            $this->recordMovementRow($resource, $user, 'damaged', $damaged, 'available', 'damaged', 'Encoded with issue/defect');
        }

        return $inventory;
    }

    /**
     * Reconcile the inventory after the encoded delivered/defect quantities
     * changed, keeping the adjustment trail in the movement history.
     */
    public function applyEncodingUpdate(
        LearningResource $resource,
        int $previousDelivered,
        int $previousDamaged,
        ?User $user = null,
    ): void {
        $inventory = $resource->inventory;

        if (! $inventory) {
            $this->initialize($resource, $user);

            return;
        }

        $deliveredDelta = (int) $resource->quantity_delivered - $previousDelivered;
        $damagedDelta = min((int) $resource->quantity_with_issue_defect, (int) $resource->quantity_delivered) - $previousDamaged;
        $availableDelta = $deliveredDelta - $damagedDelta;

        if ($deliveredDelta === 0 && $damagedDelta === 0) {
            return;
        }

        if ($inventory->available + $availableDelta < 0) {
            throw ValidationException::withMessages([
                'resources' => "Cannot reduce '{$resource->title}' below the copies already issued, borrowed, or otherwise accounted for.",
            ]);
        }

        if ($inventory->damaged + $damagedDelta < 0) {
            throw ValidationException::withMessages([
                'resources' => "Cannot reduce the damaged count of '{$resource->title}' below its current damaged copies.",
            ]);
        }

        $inventory->update([
            'available' => $inventory->available + $availableDelta,
            'damaged' => $inventory->damaged + $damagedDelta,
        ]);

        $this->recordMovementRow(
            $resource,
            $user,
            'adjustment',
            abs($deliveredDelta) + abs($damagedDelta),
            null,
            null,
            sprintf(
                'Encoding updated: delivered %+d, with issue/defect %+d',
                $deliveredDelta,
                $damagedDelta,
            ),
        );
    }

    /**
     * Record a status movement (issue, borrow, return, damage, lose, condemn).
     */
    public function recordMovement(
        LearningResource $resource,
        string $type,
        int $quantity,
        ?string $fromStatus,
        ?string $notes,
        ?User $user = null,
    ): void {
        $transition = self::TRANSITIONS[$type] ?? null;

        if (! $transition) {
            throw ValidationException::withMessages(['type' => 'Unknown inventory movement type.']);
        }

        $source = $fromStatus ?? $transition['sources'][0];

        if (! in_array($source, $transition['sources'], true)) {
            throw ValidationException::withMessages([
                'from_status' => "A '{$type}' movement cannot take copies from '{$source}'.",
            ]);
        }

        DB::transaction(function () use ($resource, $type, $quantity, $source, $transition, $notes, $user): void {
            $inventory = $resource->inventory()->lockForUpdate()->firstOrFail();
            $target = $transition['target'];

            if ($inventory->{$source} < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Only {$inventory->{$source}} cop".($inventory->{$source} === 1 ? 'y is' : 'ies are')." '{$source}' for '{$resource->title}'.",
                ]);
            }

            $inventory->update([
                $source => $inventory->{$source} - $quantity,
                $target => $inventory->{$target} + $quantity,
            ]);

            $this->recordMovementRow($resource, $user, $type, $quantity, $source, $target, $notes);
        });
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function transitionSources(): array
    {
        return collect(self::TRANSITIONS)
            ->map(fn (array $transition): array => $transition['sources'])
            ->all();
    }

    private function recordMovementRow(
        LearningResource $resource,
        ?User $user,
        string $type,
        int $quantity,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $notes,
    ): void {
        $resource->inventoryMovements()->create([
            'school_id' => $resource->school_id,
            'user_id' => $user?->id,
            'type' => $type,
            'quantity' => $quantity,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'notes' => $notes,
        ]);
    }
}
