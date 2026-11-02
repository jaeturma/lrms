<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ValidLrSchool;
use App\Models\ValidLrVisit;
use App\Services\ValidLrPreparationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only live view of VALID-LR monitoring for the LR manager, CID chief,
 * ASDS/SDS and team leaders: each school's preparation and validation
 * progress, refreshed by polling.
 */
class ValidLrBoardController extends Controller
{
    public function __invoke(Request $request, ValidLrPreparationService $preparationService): Response
    {
        $filters = $request->validate([
            'stage' => ['nullable', 'in:'.implode(',', array_keys(ValidLrVisit::STAGES))],
            'district' => ['nullable', 'string', 'max:255'],
            'leader' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $visits = ValidLrVisit::query()
            ->with(['validLrSchool.school', 'teamLeader:id,name'])
            ->when($filters['stage'] ?? null, fn (Builder $query, string $stage) => $query->inStage($stage))
            ->when($filters['leader'] ?? null, fn (Builder $query, int $leader) => $query->where('team_leader_id', $leader))
            ->when(filled($filters['district'] ?? null) || filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $query->whereHas('validLrSchool', function (Builder $schoolQuery) use ($filters): void {
                    $schoolQuery
                        ->when(filled($filters['district'] ?? null), fn (Builder $nested) => $nested->where('district', $filters['district']))
                        ->when(filled($filters['search'] ?? null), fn (Builder $nested) => $nested->where(fn (Builder $match) => $match
                            ->where('beis_school_id', 'like', $filters['search'].'%')
                            ->orWhere('school_name', 'like', '%'.$filters['search'].'%')));
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(25)
            ->withQueryString();

        $progress = $preparationService->progress($visits->getCollection()->pluck('id'));
        $withEnrolment = $preparationService->schoolsWithEnrolment(
            $visits->getCollection()->map(fn (ValidLrVisit $visit): ?int => $visit->validLrSchool->school?->id)->filter()->values()
        );

        $visits->through(function (ValidLrVisit $visit) use ($progress, $withEnrolment, $preparationService): array {
            $school = $visit->validLrSchool;
            $counts = $progress->get($visit->id, ['items_total' => 0, 'school_counted' => 0, 'validated' => 0, 'mismatched' => 0]);

            return [
                'id' => $visit->id,
                'stage' => $visit->stage(),
                'beis_school_id' => $school->beis_school_id,
                'school_name' => $school->school_name,
                'district' => $school->district,
                'in_lrms' => $school->school !== null,
                'missing_profile' => $school->school ? $preparationService->missingProfile($school->school) : null,
                'enrolment_complete' => $school->school !== null && $withEnrolment->contains($school->school->id),
                'team_leader' => $visit->teamLeader?->name,
                'scheduled_at' => $visit->scheduled_at?->format('M j, Y g:i A'),
                'school_submitted_at' => $visit->school_submitted_at?->format('M j g:i A'),
                'overridden' => $visit->overridden_at !== null,
                'updated_at' => $visit->updated_at?->diffForHumans(),
                ...$counts,
            ];
        });

        $stageCounts = collect(array_keys(ValidLrVisit::STAGES))
            ->mapWithKeys(fn (string $stage): array => [$stage => ValidLrVisit::query()->inStage($stage)->count()])
            ->all();

        return Inertia::render('AdminValidLrBoard', [
            'filters' => $filters,
            'visits' => $visits,
            'summary' => [
                'schools' => ValidLrSchool::query()->count(),
                'not_started' => ValidLrSchool::query()->doesntHave('visits')->count(),
                'stages' => $stageCounts,
            ],
            'stages' => ValidLrVisit::STAGES,
            'districts' => ValidLrSchool::query()->whereNotNull('district')->distinct()->orderBy('district')->pluck('district'),
            'leaders' => User::query()
                ->whereIn('id', ValidLrVisit::query()->whereNotNull('team_leader_id')->distinct()->pluck('team_leader_id'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'currentUserId' => $request->user()->id,
            'refreshedAt' => now()->format('g:i:s A'),
        ]);
    }
}
