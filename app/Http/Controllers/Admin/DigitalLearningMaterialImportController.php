<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportInventoryFileRequest;
use App\Services\DigitalLearningMaterialImportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DigitalLearningMaterialImportController extends Controller
{
    public function store(
        ImportInventoryFileRequest $request,
        DigitalLearningMaterialImportService $importService,
    ): JsonResponse {
        return response()->json([
            'message' => 'Digital learning materials imported successfully.',
            'summary' => $importService->import($request->file('file')),
        ]);
    }

    public function downloadTemplate(): StreamedResponse
    {
        $rows = [
            [
                'name',
                'category',
                'type',
                'publisher',
                'link',
                'description',
                'quality_assured',
                'is_active',
            ],
            [
                'Grade 7 Science Interactive Module',
                'Learning Material',
                'H5P (HTML5 Package)',
                'DepEd Learning Resources',
                'https://commons.deped.gov.ph/example',
                'Interactive science module covering matter and energy.',
                '1',
                '1',
            ],
        ];

        return $this->downloadCsv($rows, 'digital-learning-materials-import-template.csv');
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function downloadCsv(array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
