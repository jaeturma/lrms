<?php

namespace App\Services;

use App\Models\LearningResource;
use App\Models\LearningResourceInventory;
use Illuminate\Support\Facades\DB;

/**
 * Merges duplicate LearningResource rows — multiple rows for the same
 * school + catalog title, produced by receiving more than one distribution
 * of the same title before the find-or-create fix in
 * ResourceDistributionService::receive() — into a single surviving row.
 *
 * Nothing is hard-deleted: quantities and inventory counters are summed
 * onto the survivor, movement history and distribution links are re-pointed
 * onto the survivor (never lost or rewritten in place), and the duplicate
 * rows are soft-deleted with their resource_title_id cleared so they no
 * longer occupy the (school_id, resource_title_id) slot the new unique
 * index protects.
 *
 * Idempotent and safe to run repeatedly — a no-op once no duplicates remain.
 */
class LearningResourceDuplicateMerger
{
    /**
     * Merge every duplicate group currently in the table.
     *
     * @return int Number of groups merged.
     */
    public function mergeAll(): int
    {
        $groups = LearningResource::duplicateGroups();

        foreach ($groups as $group) {
            $this->mergeGroup($group['resource_ids']);
        }

        return $groups->count();
    }

    /**
     * Merge one (school_id, resource_title_id) group into its oldest row.
     *
     * @param  array<int, int>  $resourceIds  Ordered oldest-first; the first id survives.
     */
    public function mergeGroup(array $resourceIds): void
    {
        if (count($resourceIds) < 2) {
            return;
        }

        DB::transaction(function () use ($resourceIds): void {
            $survivorId = $resourceIds[0];
            $duplicateIds = array_slice($resourceIds, 1);

            $resources = LearningResource::query()
                ->whereIn('id', $resourceIds)
                ->with('inventory')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $survivor = $resources->get($survivorId);

            if (! $survivor) {
                return;
            }

            $deliveredTotal = (int) $survivor->quantity_delivered;
            $defectTotal = (int) $survivor->quantity_with_issue_defect;
            $inventoryTotals = $this->inventoryTotals($survivor->inventory);
            $mergedIds = [];

            foreach ($duplicateIds as $duplicateId) {
                $duplicate = $resources->get($duplicateId);

                if (! $duplicate) {
                    continue;
                }

                $deliveredTotal += (int) $duplicate->quantity_delivered;
                $defectTotal += (int) $duplicate->quantity_with_issue_defect;

                foreach ($this->inventoryTotals($duplicate->inventory) as $status => $value) {
                    $inventoryTotals[$status] += $value;
                }

                // Re-point movement history and the distribution link so
                // nothing is lost or orphaned once the duplicate is hidden.
                DB::table('inventory_movements')
                    ->where('learning_resource_id', $duplicateId)
                    ->update(['learning_resource_id' => $survivorId]);

                DB::table('resource_distributions')
                    ->where('learning_resource_id', $duplicateId)
                    ->update(['learning_resource_id' => $survivorId]);

                // Zero the duplicate's own ledger so it can never be
                // double-counted if it's ever queried directly (e.g. via a
                // relation that doesn't apply the soft-delete scope).
                $duplicate->inventory?->update([
                    'available' => 0,
                    'issued' => 0,
                    'borrowed' => 0,
                    'damaged' => 0,
                    'lost' => 0,
                    'condemned' => 0,
                ]);

                $duplicate->update([
                    'quantity_delivered' => 0,
                    'quantity_with_issue_defect' => 0,
                    // Release the (school_id, resource_title_id) slot so the
                    // unique index doesn't collide with this now-inert row.
                    'resource_title_id' => null,
                ]);

                $duplicate->delete();

                $mergedIds[] = $duplicateId;
            }

            if ($mergedIds === []) {
                return;
            }

            $survivor->update([
                'quantity_delivered' => $deliveredTotal,
                'quantity_with_issue_defect' => $defectTotal,
            ]);

            if ($survivor->inventory) {
                $survivor->inventory->update($inventoryTotals);
            } else {
                $survivor->inventory()->create($inventoryTotals);
            }

            $survivor->inventoryMovements()->create([
                'school_id' => $survivor->school_id,
                'user_id' => null,
                'type' => 'adjustment',
                'quantity' => count($mergedIds),
                'from_status' => null,
                'to_status' => null,
                'notes' => 'Merged '.count($mergedIds).' duplicate record(s) (IDs: '.implode(', ', $mergedIds).') into this row.',
            ]);
        });
    }

    /**
     * @return array<string, int>
     */
    private function inventoryTotals(?LearningResourceInventory $inventory): array
    {
        return [
            'available' => (int) ($inventory->available ?? 0),
            'issued' => (int) ($inventory->issued ?? 0),
            'borrowed' => (int) ($inventory->borrowed ?? 0),
            'damaged' => (int) ($inventory->damaged ?? 0),
            'lost' => (int) ($inventory->lost ?? 0),
            'condemned' => (int) ($inventory->condemned ?? 0),
        ];
    }
}
