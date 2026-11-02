<?php

namespace App\Services;

use App\Models\ValidLrSchool;
use App\Models\ValidLrVisit;
use App\Models\ValidLrVisitItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Division roll-up of VALID-LR monitoring visits: coverage per district,
 * indicator answers, delivery status, and per-visit count discrepancies.
 */
class ValidLrReportService
{
    /**
     * @param  array{status?: ?string, district?: ?string, from?: ?string, to?: ?string}  $filters
     * @return array{
     *     summary: array{schools: int, schools_monitored: int, visits: int, completed: int, drafts: int, coverage: float},
     *     districts: array<int, array{district: string, schools: int, monitored: int, completed: int, coverage: float}>,
     *     indicators: array<int, array{key: string, question: string, yes: int, no: int, unanswered: int}>,
     *     delivery: array<int, array{area: string, complete: int, incomplete: int, unrecorded: int}>,
     *     visits: array<int, array<string, mixed>>
     * }
     */
    public function build(array $filters): array
    {
        $visits = $this->visitQuery($filters)
            ->with(['validLrSchool.school', 'user:id,name', 'teamLeader:id,name', 'items'])
            ->orderByDesc('visited_at')
            ->get();

        $schoolQuery = ValidLrSchool::query()
            ->when(filled($filters['district'] ?? null), fn (Builder $query) => $query->where('district', $filters['district']));

        $schoolCount = (clone $schoolQuery)->count();
        $monitoredIds = $visits->pluck('valid_lr_school_id')->unique();
        $completedIds = $visits->where('status', 'completed')->pluck('valid_lr_school_id')->unique();

        return [
            'summary' => [
                'schools' => $schoolCount,
                'schools_monitored' => $monitoredIds->count(),
                'visits' => $visits->count(),
                'completed' => $visits->where('status', 'completed')->count(),
                'drafts' => $visits->where('status', 'draft')->count(),
                'coverage' => $this->percent($monitoredIds->count(), $schoolCount),
            ],
            'districts' => $this->districts($schoolQuery, $monitoredIds, $completedIds),
            'indicators' => $this->indicators($visits),
            'delivery' => $this->delivery($visits),
            'visits' => $visits->map(fn (ValidLrVisit $visit): array => $this->visitRow($visit))->values()->all(),
        ];
    }

    /**
     * @param  array{status?: ?string, district?: ?string, from?: ?string, to?: ?string}  $filters
     * @return Builder<ValidLrVisit>
     */
    public function visitQuery(array $filters): Builder
    {
        return ValidLrVisit::query()
            ->when(in_array($filters['status'] ?? null, ValidLrVisit::STATUSES, true), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(filled($filters['district'] ?? null), fn (Builder $query) => $query->whereHas('validLrSchool', fn (Builder $schoolQuery) => $schoolQuery->where('district', $filters['district'])))
            ->when(filled($filters['from'] ?? null), fn (Builder $query) => $query->whereDate('visited_at', '>=', $filters['from']))
            ->when(filled($filters['to'] ?? null), fn (Builder $query) => $query->whereDate('visited_at', '<=', $filters['to']));
    }

    /**
     * Whether a counted item differs from the figure monitors validate it
     * against: LRMIS for learning resources, the package quantity otherwise.
     */
    public static function hasDiscrepancy(ValidLrVisitItem $item): bool
    {
        $expected = $item->section === ValidLrVisitItem::SECTION_LEARNING_RESOURCES
            ? $item->lrmis_quantity
            : $item->reference_quantity;

        return $item->actual_quantity !== null && $expected !== null && $item->actual_quantity !== $expected;
    }

    /**
     * @return array<string, mixed>
     */
    private function visitRow(ValidLrVisit $visit): array
    {
        $counted = $visit->items->whereNotNull('actual_quantity');

        return [
            'id' => $visit->id,
            'beis_school_id' => $visit->validLrSchool->beis_school_id,
            'school_name' => $visit->validLrSchool->school_name,
            'district' => $visit->validLrSchool->district,
            'visited_at' => $visit->visited_at?->format('Y-m-d H:i'),
            'status' => $visit->status,
            'monitoring_team' => $visit->monitoring_team,
            'monitor' => $visit->user?->name,
            'stage' => $visit->stage(),
            'team_leader' => $visit->teamLeader?->name,
            'school_representative' => $visit->school_representative,
            'personnel' => $visit->personnel(),
            'lr_delivery_status' => $visit->lr_delivery_status,
            'sme_delivery_status' => $visit->sme_delivery_status,
            'tvl_delivery_status' => $visit->tvl_delivery_status,
            'indicators' => collect(ValidLrVisit::INDICATORS)
                ->map(fn (string $question, string $key): ?string => $visit->indicators[$key]['answer'] ?? null)
                ->all(),
            'items_total' => $visit->items->count(),
            'items_counted' => $counted->count(),
            'discrepancies' => $counted->filter(fn (ValidLrVisitItem $item): bool => self::hasDiscrepancy($item))->count(),
            'key_findings' => $visit->key_findings,
        ];
    }

    /**
     * @param  Builder<ValidLrSchool>  $schoolQuery
     * @param  Collection<int, int>  $monitoredIds
     * @param  Collection<int, int>  $completedIds
     * @return array<int, array{district: string, schools: int, monitored: int, completed: int, coverage: float}>
     */
    private function districts(Builder $schoolQuery, Collection $monitoredIds, Collection $completedIds): array
    {
        return (clone $schoolQuery)
            ->get(['id', 'district'])
            ->groupBy(fn (ValidLrSchool $school): string => $school->district ?: 'Unassigned')
            ->map(function (Collection $schools, string $district) use ($monitoredIds, $completedIds): array {
                $ids = $schools->pluck('id');
                $monitored = $ids->intersect($monitoredIds)->count();

                return [
                    'district' => $district,
                    'schools' => $ids->count(),
                    'monitored' => $monitored,
                    'completed' => $ids->intersect($completedIds)->count(),
                    'coverage' => $this->percent($monitored, $ids->count()),
                ];
            })
            ->sortKeys()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, ValidLrVisit>  $visits
     * @return array<int, array{key: string, question: string, yes: int, no: int, unanswered: int}>
     */
    private function indicators(Collection $visits): array
    {
        return collect(ValidLrVisit::INDICATORS)
            ->map(function (string $question, string $key) use ($visits): array {
                $answers = $visits->map(fn (ValidLrVisit $visit): ?string => $visit->indicators[$key]['answer'] ?? null);

                return [
                    'key' => $key,
                    'question' => $question,
                    'yes' => $answers->filter(fn (?string $answer): bool => $answer === 'yes')->count(),
                    'no' => $answers->filter(fn (?string $answer): bool => $answer === 'no')->count(),
                    'unanswered' => $answers->filter(fn (?string $answer): bool => $answer === null)->count(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, ValidLrVisit>  $visits
     * @return array<int, array{area: string, complete: int, incomplete: int, unrecorded: int}>
     */
    private function delivery(Collection $visits): array
    {
        return collect(['Learning Resources' => 'lr_delivery_status', 'SME' => 'sme_delivery_status', 'TVL' => 'tvl_delivery_status'])
            ->map(fn (string $column, string $area): array => [
                'area' => $area,
                'complete' => $visits->where($column, 'Complete')->count(),
                'incomplete' => $visits->where($column, 'Incomplete')->count(),
                'unrecorded' => $visits->whereNull($column)->count(),
            ])
            ->values()
            ->all();
    }

    private function percent(int $part, int $whole): float
    {
        return $whole > 0 ? round($part / $whole * 100, 1) : 0.0;
    }
}
