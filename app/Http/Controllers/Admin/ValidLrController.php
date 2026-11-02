<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ValidLrAllocation;
use App\Models\ValidLrLrmisCount;
use App\Models\ValidLrSchool;
use App\Models\ValidLrSmeItem;
use App\Models\ValidLrTitle;
use App\Models\ValidLrTvlItem;
use App\Models\ValidLrTvlProgram;
use App\Models\ValidLrVisit;
use App\Services\ValidLrSchoolProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ValidLrController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $search = trim($request->string('search')->toString());

        $exactMatch = $search !== '' ? ValidLrSchool::query()->where('beis_school_id', $search)->first() : null;

        if ($exactMatch && $request->boolean('lookup')) {
            return redirect()->route('admin.valid-lr.schools.show', $exactMatch);
        }

        $schools = ValidLrSchool::query()
            ->withCount('visits')
            ->with('school:id,school_id')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('beis_school_id', 'like', "{$search}%")
                        ->orWhere('school_name', 'like', "%{$search}%")
                        ->orWhere('district', 'like', "%{$search}%");
                });
            })
            ->orderBy('school_name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (ValidLrSchool $school): array => [
                'beis_school_id' => $school->beis_school_id,
                'school_name' => $school->school_name,
                'district' => $school->district,
                'municipality' => $school->municipality,
                'curricular_offering' => $school->curricular_offering,
                'in_lrms' => $school->school !== null,
                'visits_count' => $school->visits_count,
            ]);

        $recentVisits = ValidLrVisit::query()
            ->with(['validLrSchool:id,beis_school_id,school_name', 'user:id,name'])
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(fn (ValidLrVisit $visit): array => [
                'id' => $visit->id,
                'status' => $visit->status,
                'visited_at' => $visit->visited_at?->toDateTimeString(),
                'updated_at' => $visit->updated_at?->toDateTimeString(),
                'beis_school_id' => $visit->validLrSchool->beis_school_id,
                'school_name' => $visit->validLrSchool->school_name,
                'monitor' => $visit->user?->name,
            ]);

        return Inertia::render('AdminValidLr', [
            'filters' => ['search' => $search],
            'schools' => $schools,
            'recentVisits' => $recentVisits,
            'divisions' => ValidLrSchool::query()->whereNotNull('division')->distinct()->orderBy('division')->pluck('division'),
            'stats' => [
                'schools' => ValidLrSchool::query()->count(),
                'allocations' => ValidLrAllocation::query()->count(),
                'titles' => ValidLrTitle::query()->count(),
                'lrmis_counts' => ValidLrLrmisCount::query()->count(),
                'sme_schools' => ValidLrSchool::query()->whereNotNull('sme_deliveries')->where('sme_deliveries', '!=', '[]')->count(),
                'sme_items' => ValidLrSmeItem::query()->count(),
                'tvl_items' => ValidLrTvlItem::query()->count(),
                'tvl_programs' => ValidLrTvlProgram::query()->count(),
                'visits' => ValidLrVisit::query()->count(),
            ],
            'canImport' => in_array($request->user()?->role, ['admin', 'superadmin', 'sysadmin', 'ito'], true),
            'canSchedule' => ValidLrVisit::canSchedule($request->user()),
        ]);
    }

    public function show(Request $request, ValidLrSchool $validLrSchool, ValidLrSchoolProfileService $profileService): Response
    {
        $validLrSchool->load('school');
        $openVisit = $validLrSchool->visits()->with('teamLeader:id,name')->where('status', '!=', 'completed')->latest('id')->first();
        $canSchedule = ValidLrVisit::canSchedule($request->user());

        return Inertia::render('AdminValidLrSchool', [
            'school' => [
                'beis_school_id' => $validLrSchool->beis_school_id,
                'school_name' => $validLrSchool->school_name,
                'region' => $validLrSchool->region,
                'division' => $validLrSchool->division,
                'district' => $validLrSchool->district,
                'municipality' => $validLrSchool->municipality,
                'barangay' => $validLrSchool->barangay,
                'school_type' => $validLrSchool->school_type,
                'curricular_offering' => $validLrSchool->curricular_offering,
                'lrms_school' => $validLrSchool->school ? [
                    'school_id' => $validLrSchool->school->school_id,
                    'school_name' => $validLrSchool->school->school_name,
                ] : null,
                'personnel' => collect(ValidLrVisit::PERSONNEL)
                    ->map(fn (string $label, string $field): ?string => $validLrSchool->school?->{$field} ?: null)
                    ->all(),
            ],
            'personnelLabels' => ValidLrVisit::PERSONNEL,
            'openVisit' => $openVisit ? [
                'id' => $openVisit->id,
                'stage' => $openVisit->stage(),
                'team_leader_id' => $openVisit->team_leader_id,
                'team_leader' => $openVisit->teamLeader?->name,
                'scheduled_at' => $openVisit->scheduled_at?->format('Y-m-d\TH:i'),
                'monitoring_team' => $openVisit->monitoring_team,
            ] : null,
            'stages' => ValidLrVisit::STAGES,
            'canSchedule' => $canSchedule,
            'teamLeaders' => $canSchedule ? ValidLrVisit::teamLeaderOptions() : [],
            'profile' => $profileService->build($validLrSchool),
            'visits' => $validLrSchool->visits()
                ->with('user:id,name')
                ->latest('visited_at')
                ->get()
                ->map(fn (ValidLrVisit $visit): array => [
                    'id' => $visit->id,
                    'status' => $visit->status,
                    'stage' => $visit->stage(),
                    'visited_at' => $visit->visited_at?->toDateTimeString(),
                    'monitor' => $visit->user?->name,
                    'monitoring_team' => $visit->monitoring_team,
                ]),
        ]);
    }
}
