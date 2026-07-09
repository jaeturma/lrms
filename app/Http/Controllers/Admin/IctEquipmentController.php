<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IctEquipment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IctEquipmentController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $category = $request->string('category')->toString();
        $status = $request->string('status')->toString();

        $equipment = IctEquipment::query()
            ->with('school:id,school_id,school_name')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('item_name', 'like', "%{$search}%")
                        ->orWhere('item_code', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('property_number', 'like', "%{$search}%")
                        ->orWhereHas('school', function ($schoolQuery) use ($search): void {
                            $schoolQuery
                                ->where('school_name', 'like', "%{$search}%")
                                ->orWhere('school_id', 'like', "%{$search}%");
                        });
                });
            })
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('item_name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (IctEquipment $item): array => [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'item_name' => $item->item_name,
                'category' => $item->category,
                'brand' => $item->brand,
                'model' => $item->model,
                'condition' => $item->condition,
                'status' => $item->status,
                'assigned_personnel' => $item->assigned_personnel,
                'school_id' => $item->school?->school_id,
                'school_name' => $item->school?->school_name,
            ]);

        return Inertia::render('AdminIctEquipment', [
            'filters' => [
                'search' => $search,
                'category' => $category !== '' ? $category : null,
                'status' => $status !== '' ? $status : null,
            ],
            'categories' => IctEquipment::CATEGORIES,
            'statuses' => IctEquipment::STATUSES,
            'equipment' => $equipment,
            'summary' => [
                'total' => IctEquipment::count(),
                'by_status' => IctEquipment::query()
                    ->selectRaw('status, count(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status'),
            ],
        ]);
    }
}
