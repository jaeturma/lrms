<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryMovementRequest;
use App\Models\InventoryMovement;
use App\Models\LearningResource;
use App\Models\School;
use App\Services\LearningResourceInventoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SchoolInventoryController extends Controller
{
    public function index(): Response
    {
        $school = $this->resolveSchool();

        $resources = $school->learningResources()
            ->with(['learningResourceType:id,name,category', 'inventory'])
            ->orderBy('title')
            ->get()
            ->map(fn (LearningResource $resource): array => [
                'id' => $resource->id,
                'title' => $resource->title,
                'resource_type' => $resource->learningResourceType?->name,
                'publisher' => $resource->publisher,
                'inventory' => [
                    'available' => $resource->inventory?->available ?? 0,
                    'issued' => $resource->inventory?->issued ?? 0,
                    'borrowed' => $resource->inventory?->borrowed ?? 0,
                    'damaged' => $resource->inventory?->damaged ?? 0,
                    'lost' => $resource->inventory?->lost ?? 0,
                    'condemned' => $resource->inventory?->condemned ?? 0,
                ],
            ]);

        $movements = InventoryMovement::query()
            ->where('school_id', $school->id)
            ->with(['learningResource:id,title', 'user:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (InventoryMovement $movement): array => [
                'id' => $movement->id,
                'learning_resource_id' => $movement->learning_resource_id,
                'resource_title' => $movement->learningResource?->title,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'from_status' => $movement->from_status,
                'to_status' => $movement->to_status,
                'notes' => $movement->notes,
                'recorded_by' => $movement->user?->name,
                'created_at' => $movement->created_at?->toIso8601String(),
            ]);

        return Inertia::render('SchoolInventory', [
            'resources' => $resources,
            'movements' => $movements,
            'transitionSources' => LearningResourceInventoryService::transitionSources(),
        ]);
    }

    public function storeMovement(
        StoreInventoryMovementRequest $request,
        LearningResource $learningResource,
        LearningResourceInventoryService $inventoryService,
    ): RedirectResponse {
        $school = $this->resolveSchool();

        abort_if($learningResource->school_id !== $school->id, 403);

        $inventoryService->recordMovement(
            $learningResource,
            $request->validated('type'),
            (int) $request->validated('quantity'),
            $request->validated('from_status'),
            $request->validated('notes'),
            $request->user(),
        );

        return back()->with('status', 'Inventory movement recorded.');
    }

    private function resolveSchool(): School
    {
        $school = auth()->user()?->school;

        abort_if(! $school, 403);

        return $school;
    }
}
