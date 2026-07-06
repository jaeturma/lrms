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
}
