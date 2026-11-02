<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateValidLrSchoolCountRequest;
use App\Models\School;
use App\Models\ValidLrSchool;
use App\Models\ValidLrVisit;
use App\Services\ValidLrPreparationService;
use App\Services\ValidLrVisitService;
use App\Support\ValidLrVisitPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The school's side of VALID-LR: complete the profile, encode enrolment and
 * count every delivered item before the monitoring team validates on site.
 * Counts stay editable until the team completes the visit.
 */
class SchoolValidLrController extends Controller
{
    public function index(Request $request, ValidLrVisitService $visitService, ValidLrPreparationService $preparationService): Response
    {
        $school = $this->resolveSchool($request);
        $validLrSchool = ValidLrSchool::query()->where('beis_school_id', $school->school_id)->first();

        $visit = $validLrSchool
            ? ($visitService->openVisitFor($validLrSchool, createIfMissing: true) ?? $validLrSchool->visits()->latest('id')->first())
            : null;

        return Inertia::render('SchoolValidLr', [
            'available' => $visit !== null,
            'visit' => $visit ? ValidLrVisitPresenter::visit($visit) : null,
            'school' => $validLrSchool ? ValidLrVisitPresenter::school($validLrSchool) : null,
            'checklist' => $visit ? $preparationService->checklist($visit) : null,
            'canEdit' => $visit?->isOpen() ?? false,
            'profileHref' => route('school.activate.edit', $school),
            'enrolmentHref' => route('school.enrollment.index'),
            ...ValidLrVisitPresenter::options(),
        ]);
    }

    public function update(UpdateValidLrSchoolCountRequest $request, ValidLrVisitService $visitService): RedirectResponse
    {
        $visit = $this->openVisit($request, $visitService);

        $visitService->updateBySchool($visit, $request->validated());

        return back()->with('status', 'Your counts were saved.');
    }

    /**
     * Saves any counts sent with the submission, then submits if the
     * checklist is complete.
     */
    public function submit(UpdateValidLrSchoolCountRequest $request, ValidLrVisitService $visitService, ValidLrPreparationService $preparationService): RedirectResponse
    {
        $visit = $this->openVisit($request, $visitService);
        $visitService->updateBySchool($visit, $request->validated());
        $checklist = $preparationService->checklist($visit);

        if (! $checklist['ready']) {
            return back()->withErrors([
                'submit' => 'Complete the checklist before submitting: '.implode('; ', array_filter([
                    $checklist['profile_complete'] ? null : 'school profile ('.implode(', ', $checklist['missing_profile']).')',
                    $checklist['enrolment_complete'] ? null : 'enrolment for the current school year',
                    $checklist['counts_complete'] ? null : sprintf('counts for all items (%d of %d done)', $checklist['items_counted'], $checklist['items_total']),
                ])).'.',
            ]);
        }

        $visitService->submitBySchool($visit, $request->user());

        return back()->with('status', 'Submitted. The monitoring team will validate your counts on site; you can still update them until the visit is completed.');
    }

    private function openVisit(Request $request, ValidLrVisitService $visitService): ValidLrVisit
    {
        $school = $this->resolveSchool($request);
        $validLrSchool = ValidLrSchool::query()->where('beis_school_id', $school->school_id)->first();

        abort_if(! $validLrSchool, 404, 'Your school is not in the VALID-LR monitoring list.');

        $visit = $visitService->openVisitFor($validLrSchool, createIfMissing: true);

        abort_if(! $visit, 422, 'Monitoring for your school is already completed.');

        return $visit;
    }

    private function resolveSchool(Request $request): School
    {
        $school = $request->user()?->school;

        abort_if(! $school, 403);

        return $school;
    }
}
