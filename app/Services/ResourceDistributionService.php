<?php

namespace App\Services;

use App\Models\LearningResource;
use App\Models\ResourceDistribution;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResourceDistributionService
{
    public function __construct(private LearningResourceInventoryService $inventoryService) {}

    /**
     * Record a division-to-school delivery awaiting the school's confirmation.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): ResourceDistribution
    {
        return DB::transaction(function () use ($data, $user): ResourceDistribution {
            $distribution = ResourceDistribution::create([
                ...$data,
                'status' => 'pending',
                'created_by' => $user->id,
            ]);

            $distribution->reference_code = sprintf('DST-%s-%05d', now()->year, $distribution->id);
            $distribution->save();

            return $distribution;
        });
    }

    public function cancel(ResourceDistribution $distribution): void
    {
        $this->ensurePending($distribution, 'Only pending deliveries can be cancelled.');

        $distribution->update(['status' => 'cancelled']);
    }

    /**
     * Confirm the school received the delivery, encoding it as a learning
     * resource whose inventory opens with the delivered and damaged copies.
     */
    public function receive(ResourceDistribution $distribution, int $quantityDamaged, User $user): LearningResource
    {
        $this->ensurePending($distribution, 'This delivery has already been received or cancelled.');

        return DB::transaction(function () use ($distribution, $quantityDamaged, $user): LearningResource {
            $resource = LearningResource::create([
                'school_id' => $distribution->school_id,
                'learning_resource_type_id' => $distribution->learning_resource_type_id,
                'title' => $distribution->title,
                'publisher' => $distribution->publisher,
                'quantity_delivered' => $distribution->quantity,
                'quantity_with_issue_defect' => $quantityDamaged,
                'remarks' => "Received from division delivery {$distribution->reference_code}",
            ]);

            $this->inventoryService->initialize($resource, $user);

            $distribution->update([
                'status' => 'received',
                'quantity_damaged' => $quantityDamaged,
                'received_by' => $user->id,
                'received_at' => now(),
                'learning_resource_id' => $resource->id,
            ]);

            return $resource;
        });
    }

    private function ensurePending(ResourceDistribution $distribution, string $message): void
    {
        if (! $distribution->isPending()) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }
}
