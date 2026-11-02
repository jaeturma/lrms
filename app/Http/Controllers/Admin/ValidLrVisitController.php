<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateValidLrVisitRequest;
use App\Models\ValidLrSchool;
use App\Models\ValidLrVisit;
use App\Models\ValidLrVisitItem;
use App\Services\ValidLrVisitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ValidLrVisitController extends Controller
{
    public function store(Request $request, ValidLrSchool $validLrSchool, ValidLrVisitService $visitService): RedirectResponse
    {
        $visit = $visitService->start($validLrSchool, $request->user());

        return redirect()
            ->route('admin.valid-lr.visits.edit', $visit)
            ->with('status', 'Monitoring visit started from the current reference data.');
    }

    public function edit(ValidLrVisit $validLrVisit): Response
    {
        $validLrVisit->load(['validLrSchool', 'items', 'user:id,name']);
        $school = $validLrVisit->validLrSchool;

        return Inertia::render('AdminValidLrVisit', [
            'visit' => [
                'id' => $validLrVisit->id,
                'status' => $validLrVisit->status,
                'visited_at' => $validLrVisit->visited_at?->format('Y-m-d\TH:i'),
                'monitoring_team' => $validLrVisit->monitoring_team,
                'school_representative' => $validLrVisit->school_representative,
                'indicators' => $validLrVisit->indicators ?? [],
                'lr_delivery_status' => $validLrVisit->lr_delivery_status,
                'sme_delivery_status' => $validLrVisit->sme_delivery_status,
                'tvl_delivery_status' => $validLrVisit->tvl_delivery_status,
                'key_findings' => $validLrVisit->key_findings,
                'recommended_actions' => $validLrVisit->recommended_actions,
                'issues_concerns' => $validLrVisit->issues_concerns,
                'actions_taken' => $validLrVisit->actions_taken,
                'prepared_by' => $validLrVisit->prepared_by,
                'acknowledged_by' => $validLrVisit->acknowledged_by,
                'monitor' => $validLrVisit->user?->name,
                'items' => $validLrVisit->items->map(fn (ValidLrVisitItem $item): array => [
                    'id' => $item->id,
                    'section' => $item->section,
                    'group_label' => $item->group_label,
                    'label' => $item->label,
                    'title' => $item->title,
                    'publisher' => $item->publisher,
                    'unit' => $item->unit,
                    'procurement_source' => $item->procurement_source,
                    'reference_quantity' => $item->reference_quantity,
                    'lrmis_quantity' => $item->lrmis_quantity,
                    'lrms_quantity' => $item->lrms_quantity,
                    'distributed_quantity' => $item->distributed_quantity,
                    'actual_quantity' => $item->actual_quantity,
                    'enrolment' => $item->enrolment,
                    'year_received' => $item->year_received,
                    'mov' => $item->mov ?? [],
                    'extra' => $item->extra ?? [],
                    'remarks_repairs' => $item->remarks_repairs,
                    'remarks_missing' => $item->remarks_missing,
                    'remarks_others' => $item->remarks_others,
                ])->values(),
            ],
            'school' => [
                'beis_school_id' => $school->beis_school_id,
                'school_name' => $school->school_name,
                'region' => $school->region,
                'division' => $school->division,
                'district' => $school->district,
                'curricular_offering' => $school->curricular_offering,
            ],
            'indicatorQuestions' => ValidLrVisit::INDICATORS,
            'movOptions' => ValidLrVisitItem::MOV_OPTIONS,
            'aralPrograms' => ValidLrVisitItem::ARAL_PROGRAMS,
            'deliveryStatuses' => ValidLrVisit::DELIVERY_STATUSES,
        ]);
    }

    public function update(UpdateValidLrVisitRequest $request, ValidLrVisit $validLrVisit, ValidLrVisitService $visitService): RedirectResponse
    {
        $visitService->update($validLrVisit, $request->validated());

        return back()->with('status', 'Monitoring visit saved.');
    }

    public function destroy(ValidLrVisit $validLrVisit): RedirectResponse
    {
        $school = $validLrVisit->validLrSchool;
        $validLrVisit->delete();

        return redirect()
            ->route('admin.valid-lr.schools.show', $school)
            ->with('status', 'Monitoring visit deleted.');
    }
}
