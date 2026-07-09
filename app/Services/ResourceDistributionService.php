<?php

namespace App\Services;

use App\Models\LearningResource;
use App\Models\ResourceDistribution;
use App\Models\ResourceTitle;
use App\Models\User;
use Illuminate\Database\QueryException;
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
            $resourceTitle = ResourceTitle::query()
                ->whereKey($data['resource_title_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $distribution = ResourceDistribution::create([
                ...$data,
                'learning_resource_type_id' => $resourceTitle->learning_resource_type_id,
                'title' => $resourceTitle->title,
                'publisher' => $resourceTitle->publisher,
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
     * Confirm the school received the delivery. If the school already has a
     * learning resource for this catalog title, the delivery is added to
     * its existing inventory instead of creating a duplicate row.
     */
    public function receive(ResourceDistribution $distribution, int $quantityDamaged, User $user): LearningResource
    {
        $this->ensurePending($distribution, 'This delivery has already been received or cancelled.');

        return DB::transaction(function () use ($distribution, $quantityDamaged, $user): LearningResource {
            $resource = $this->findOrCreateResource($distribution, $quantityDamaged, $user);

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

    /**
     * Reuse the school's existing learning resource for this catalog title
     * when one already exists, otherwise create a new one. Guards against a
     * race between the lookup and the insert by catching the unique-index
     * violation and falling back to the now-existing row.
     */
    private function findOrCreateResource(ResourceDistribution $distribution, int $quantityDamaged, User $user): LearningResource
    {
        $existing = $this->findExistingResource($distribution);

        if ($existing) {
            $this->inventoryService->receiveAdditional($existing, $distribution->quantity, $quantityDamaged, $user);

            return $existing;
        }

        try {
            $resource = $this->createResourceFromDistribution($distribution, $quantityDamaged);
        } catch (QueryException $exception) {
            if (! $this->isDuplicateKeyViolation($exception)) {
                throw $exception;
            }

            $existing = $this->findExistingResource($distribution);

            if (! $existing) {
                throw $exception;
            }

            $this->inventoryService->receiveAdditional($existing, $distribution->quantity, $quantityDamaged, $user);

            return $existing;
        }

        $this->inventoryService->initialize($resource, $user);

        return $resource;
    }

    private function findExistingResource(ResourceDistribution $distribution): ?LearningResource
    {
        if (! $distribution->resource_title_id) {
            return null;
        }

        return LearningResource::query()
            ->where('school_id', $distribution->school_id)
            ->where('resource_title_id', $distribution->resource_title_id)
            ->lockForUpdate()
            ->first();
    }

    private function createResourceFromDistribution(ResourceDistribution $distribution, int $quantityDamaged): LearningResource
    {
        return LearningResource::create([
            'school_id' => $distribution->school_id,
            'learning_resource_type_id' => $distribution->learning_resource_type_id,
            'resource_title_id' => $distribution->resource_title_id,
            'title' => $distribution->title,
            'author' => $distribution->resourceTitle?->author,
            'publisher' => $distribution->publisher,
            'language' => $distribution->resourceTitle?->language,
            'subject' => $distribution->resourceTitle?->subject,
            'volume' => $distribution->resourceTitle?->volume,
            'edition' => $distribution->resourceTitle?->edition,
            'copyright_year' => $distribution->resourceTitle?->copyright_year,
            'pages' => $distribution->resourceTitle?->pages,
            'isbn' => $distribution->resourceTitle?->isbn,
            'quantity_delivered' => $distribution->quantity,
            'quantity_with_issue_defect' => $quantityDamaged,
            'remarks' => "Received from division delivery {$distribution->reference_code}",
        ]);
    }

    private function isDuplicateKeyViolation(QueryException $exception): bool
    {
        return in_array($exception->getCode(), ['23000', '23505'], true);
    }

    private function ensurePending(ResourceDistribution $distribution, string $message): void
    {
        if (! $distribution->isPending()) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }
}
