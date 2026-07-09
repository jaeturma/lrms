<?php

namespace App\Http\Controllers;

use App\Models\ResourceDistribution;
use App\Models\School;
use App\Services\ResourceDistributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SchoolDistributionController extends Controller
{
    public function index(): Response
    {
        $school = $this->resolveSchool();

        $distributions = ResourceDistribution::query()
            ->where('school_id', $school->id)
            ->with(['learningResourceType:id,name', 'resourceTitle:id,author', 'receiver:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ResourceDistribution $distribution): array => [
                'id' => $distribution->id,
                'reference_code' => $distribution->reference_code,
                'resource_type' => $distribution->learningResourceType?->name,
                'title' => $distribution->title,
                'author' => $distribution->resourceTitle?->author,
                'publisher' => $distribution->publisher,
                'quantity' => $distribution->quantity,
                'quantity_damaged' => $distribution->quantity_damaged,
                'status' => $distribution->status,
                'notes' => $distribution->notes,
                'received_by' => $distribution->receiver?->name,
                'received_at' => $distribution->received_at?->toIso8601String(),
                'created_at' => $distribution->created_at?->toIso8601String(),
            ]);

        return Inertia::render('SchoolDistributions', [
            'distributions' => $distributions,
        ]);
    }

    public function receive(
        Request $request,
        ResourceDistribution $distribution,
        ResourceDistributionService $distributionService,
    ): RedirectResponse {
        $school = $this->resolveSchool();

        abort_if($distribution->school_id !== $school->id, 403);

        $validated = $request->validate([
            'quantity_damaged' => ['nullable', 'integer', 'min:0', "max:{$distribution->quantity}"],
        ]);

        $distributionService->receive($distribution, (int) ($validated['quantity_damaged'] ?? 0), $request->user());

        return back()->with('status', "Delivery {$distribution->reference_code} received into your inventory.");
    }

    private function resolveSchool(): School
    {
        $school = auth()->user()?->school;

        abort_if(! $school, 403);

        return $school;
    }
}
