<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportInventoryFileRequest;
use App\Services\SmeCatalogImportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SmeImportController extends Controller
{
    public function store(
        ImportInventoryFileRequest $request,
        SmeCatalogImportService $importService,
    ): JsonResponse {
        return response()->json([
            'message' => 'SME catalog imported successfully.',
            'summary' => $importService->import($request->file('file')),
        ]);
    }

    public function downloadTemplate(): StreamedResponse
    {
        $rows = [
            [
                'item_name',
                'category',
                'brand',
                'model',
                'specifications',
                'manufacturer',
                'description',
                'is_active',
            ],
            [
                'Microscope',
                'Science',
                'AmScope',
                'CS-100',
                'Compound microscope for science laboratory use.',
                'AmScope',
                'Science equipment for observing specimens during laboratory activities.',
                '1',
            ],
        ];

        return $this->downloadCsv($rows, 'sme-catalog-import-template.csv');
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
