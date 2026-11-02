<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateValidLrVisitRequest;
use App\Models\ValidLrSchool;
use App\Models\ValidLrVisit;
use App\Services\ValidLrPreparationService;
use App\Services\ValidLrVisitService;
use App\Support\ValidLrVisitPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ValidLrVisitController extends Controller
{
    /**
     * Schedules monitoring for a school. The school's open visit is reused
     * (it may already hold the school's own counts); otherwise a new visit
     * is started from the current reference data.
     */
    public function store(Request $request, ValidLrSchool $validLrSchool, ValidLrVisitService $visitService): RedirectResponse
    {
        abort_unless(ValidLrVisit::canSchedule($request->user()), 403);

        $assignment = $this->validatedAssignment($request);
        $visit = $visitService->openVisitFor($validLrSchool);

        if ($visit) {
            $visit->update($assignment);
            $message = 'Monitoring schedule updated.';
        } else {
            $visit = $visitService->start($validLrSchool, $request->user(), $assignment);
            $message = 'Monitoring scheduled. The school can now complete its profile and counts.';
        }

        return redirect()
            ->route('admin.valid-lr.visits.edit', $visit)
            ->with('status', $message);
    }

    public function assign(Request $request, ValidLrVisit $validLrVisit): RedirectResponse
    {
        abort_unless(ValidLrVisit::canSchedule($request->user()), 403);
        abort_unless($validLrVisit->isOpen(), 422, 'This visit is already completed.');

        $validLrVisit->update($this->validatedAssignment($request));

        return back()->with('status', 'Monitoring schedule updated.');
    }

    /**
     * Lets validation proceed without the school's submission, e.g. when the
     * school has no LRMS account. The reason is kept on the visit.
     */
    public function override(Request $request, ValidLrVisit $validLrVisit): RedirectResponse
    {
        abort_unless(ValidLrVisit::isAdmin($request->user()), 403);

        $validated = $request->validate([
            'override_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $validLrVisit->update([
            'override_reason' => $validated['override_reason'],
            'overridden_by' => $request->user()->id,
            'overridden_at' => now(),
        ]);

        return back()->with('status', 'Validation can now proceed without the school submission.');
    }

    /**
     * Superadmin, system admin, LR manager and ITO correct the visit's
     * LRMIS-encoded counts to match the school's LRMIS records.
     */
    public function adjustLrmis(Request $request, ValidLrVisit $validLrVisit, ValidLrVisitService $visitService): RedirectResponse
    {
        abort_unless(ValidLrVisit::canAdjustLrmis($request->user()), 403);

        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.lrmis_quantity' => ['nullable', 'integer', 'min:0', 'max:10000000'],
        ]);

        $changed = $visitService->adjustLrmis($validLrVisit, $validated['items'], $request->user());

        return back()->with('status', $changed === 1 ? '1 LRMIS count updated.' : "{$changed} LRMIS counts updated.");
    }

    public function edit(Request $request, ValidLrVisit $validLrVisit, ValidLrPreparationService $preparationService): Response
    {
        $user = $request->user();
        $canSchedule = ValidLrVisit::canSchedule($user);

        return Inertia::render('AdminValidLrVisit', [
            ...$this->visitProps($validLrVisit),
            'checklist' => $preparationService->checklist($validLrVisit),
            'liveSchoolCounts' => fn (): array => $validLrVisit->items()->pluck('school_quantity', 'id')->all(),
            'liveStage' => fn (): array => [
                'stage' => $validLrVisit->refresh()->stage(),
                'school_submitted_at' => $validLrVisit->school_submitted_at?->format('M j, Y g:i A'),
                'school_submitted_by' => $validLrVisit->schoolSubmitter?->name,
            ],
            'canEdit' => $validLrVisit->canBeEditedBy($user),
            'canSchedule' => $canSchedule,
            'canOverride' => ValidLrVisit::isAdmin($user),
            'canDelete' => ValidLrVisit::isAdmin($user),
            'canAdjustLrmis' => ValidLrVisit::canAdjustLrmis($user),
            'teamLeaders' => $canSchedule ? ValidLrVisit::teamLeaderOptions() : [],
        ]);
    }

    /**
     * Printable copy of a visit: blank count fields for a draft can be filled
     * in by hand on site, a completed visit prints as the signed record.
     */
    public function print(Request $request, ValidLrVisit $validLrVisit): Response
    {
        return Inertia::render('AdminValidLrVisitPrint', $this->visitProps($validLrVisit) + [
            'generatedAt' => now()->format('F j, Y g:i A'),
            'generatedBy' => $request->user()?->name,
        ]);
    }

    public function update(UpdateValidLrVisitRequest $request, ValidLrVisit $validLrVisit, ValidLrVisitService $visitService): RedirectResponse
    {
        if ($validLrVisit->stage() === ValidLrVisit::STAGE_AWAITING_SCHOOL) {
            return back()->withErrors([
                'status' => 'The school has not submitted its profile and counts yet. Validation can start once the school submits, or after a system admin overrides.',
            ]);
        }

        $data = $request->validated();

        if (($data['status'] ?? null) === 'completed' && $validLrVisit->isOpen()) {
            $data['school_personnel'] = $validLrVisit->personnel();
        }

        $visitService->update($validLrVisit, $data);

        return back()->with('status', 'Monitoring visit saved.');
    }

    public function destroy(Request $request, ValidLrVisit $validLrVisit): RedirectResponse
    {
        abort_unless(ValidLrVisit::isAdmin($request->user()), 403);

        $school = $validLrVisit->validLrSchool;
        $validLrVisit->delete();

        return redirect()
            ->route('admin.valid-lr.schools.show', $school)
            ->with('status', 'Monitoring visit deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function visitProps(ValidLrVisit $validLrVisit): array
    {
        return [
            'visit' => ValidLrVisitPresenter::visit($validLrVisit),
            'school' => ValidLrVisitPresenter::school($validLrVisit->validLrSchool),
            ...ValidLrVisitPresenter::options(),
        ];
    }

    /**
     * @return array{team_leader_id?: ?int, scheduled_at?: ?string, monitoring_team?: ?string}
     */
    private function validatedAssignment(Request $request): array
    {
        $validated = $request->validate([
            'team_leader_id' => ['nullable', 'integer', Rule::exists('users', 'id')->whereIn('role', ValidLrVisit::TEAM_LEADER_ROLES)],
            'scheduled_at' => ['nullable', 'date'],
            'monitoring_team' => ['nullable', 'string', 'max:1000'],
        ]);

        // Only the fields the scheduler sent are changed.
        return collect($validated)
            ->only(['team_leader_id', 'scheduled_at', 'monitoring_team'])
            ->all();
    }
}
