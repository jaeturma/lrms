<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportSchoolsRequest;
use App\Services\SchoolImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolImportController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('SchoolImportPage', [
            'summary' => $request->session()->get('importSummary'),
        ]);
    }

    public function store(ImportSchoolsRequest $request, SchoolImportService $importService): RedirectResponse|JsonResponse
    {
        $summary = $importService->import($request->file('csv'));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'School CSV imported successfully.',
                'summary' => $summary,
            ]);
        }

        return redirect()
            ->route('admin.import.index')
            ->with('importSummary', $summary);
    }

    public function downloadTemplate(): StreamedResponse
    {
        $rows = [
            ['municipality', 'district', 'barangay', 'school_id', 'school_name'],
            ['San Isidro', 'District I', 'Poblacion', 'SID-10001', 'San Isidro National High School'],
            ['San Isidro', 'District I', 'North Baybay', 'SID-10002', 'North Baybay Elementary School'],
            ['Santa Maria', 'District II', '', 'SID-20001', 'Santa Maria Central School'],
            ['', '', '', 'SID-30001', 'School With Pending Location'],
        ];

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 'school-import-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
