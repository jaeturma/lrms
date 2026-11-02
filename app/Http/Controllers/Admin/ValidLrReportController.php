<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ValidLrSchool;
use App\Models\ValidLrVisit;
use App\Services\ValidLrReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ValidLrReportController extends Controller
{
    public function index(Request $request, ValidLrReportService $reportService): Response
    {
        return Inertia::render('AdminValidLrReport', $this->reportProps($request, $reportService));
    }

    public function print(Request $request, ValidLrReportService $reportService): Response
    {
        return Inertia::render('AdminValidLrReportPrint', $this->reportProps($request, $reportService) + [
            'generatedAt' => now()->format('F j, Y g:i A'),
            'generatedBy' => $request->user()?->name,
        ]);
    }

    public function export(Request $request, ValidLrReportService $reportService): StreamedResponse
    {
        $report = $reportService->build($this->filters($request));
        $indicatorKeys = array_keys(ValidLrVisit::INDICATORS);

        return response()->streamDownload(function () use ($report, $indicatorKeys): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'School ID', 'School', 'District', 'Visited At', 'Status', 'Monitoring Team', 'Team Leader', 'Monitor',
                ...array_values(ValidLrVisit::PERSONNEL),
                'LR Delivery', 'SME Delivery', 'TVL Delivery',
                ...array_map(fn (string $key): string => 'Indicator: '.str_replace('_', ' ', $key), $indicatorKeys),
                'Items', 'Items Counted', 'Discrepancies', 'Key Findings',
            ]);

            foreach ($report['visits'] as $visit) {
                fputcsv($handle, [
                    $visit['beis_school_id'], $visit['school_name'], $visit['district'], $visit['visited_at'],
                    ucfirst($visit['status']), $visit['monitoring_team'], $visit['team_leader'], $visit['monitor'],
                    ...array_map(fn (string $field): ?string => $visit['personnel'][$field] ?? null, array_keys(ValidLrVisit::PERSONNEL)),
                    $visit['lr_delivery_status'], $visit['sme_delivery_status'], $visit['tvl_delivery_status'],
                    ...array_map(fn (string $key): string => ucfirst($visit['indicators'][$key] ?? ''), $indicatorKeys),
                    $visit['items_total'], $visit['items_counted'], $visit['discrepancies'], $visit['key_findings'],
                ]);
            }

            fclose($handle);
        }, 'valid-lr-monitoring-report.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportProps(Request $request, ValidLrReportService $reportService): array
    {
        $filters = $this->filters($request);

        return [
            'filters' => $filters,
            'report' => $reportService->build($filters),
            'districts' => ValidLrSchool::query()->whereNotNull('district')->distinct()->orderBy('district')->pluck('district'),
            'division' => ValidLrSchool::query()->whereNotNull('division')->value('division'),
            'indicatorQuestions' => ValidLrVisit::INDICATORS,
            'personnelLabels' => ValidLrVisit::PERSONNEL,
        ];
    }

    /**
     * @return array{status: ?string, district: ?string, from: ?string, to: ?string}
     */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:'.implode(',', ValidLrVisit::STATUSES)],
            'district' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            'status' => $validated['status'] ?? null,
            'district' => $validated['district'] ?? null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ];
    }
}
