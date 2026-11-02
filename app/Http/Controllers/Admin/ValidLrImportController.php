<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportValidLrFileRequest;
use App\Services\ValidLrReferenceImportService;
use Illuminate\Http\JsonResponse;

class ValidLrImportController extends Controller
{
    public function store(ImportValidLrFileRequest $request, ValidLrReferenceImportService $importService): JsonResponse
    {
        $summary = $importService->import(
            $request->validated('type'),
            $request->file('file')->getRealPath(),
            $request->validated('division'),
        );

        return response()->json([
            'message' => 'VALID-LR reference data imported successfully.',
            'summary' => $summary,
        ]);
    }
}
