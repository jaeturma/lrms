<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportInventoryFileRequest;
use App\Services\OtherEquipmentCatalogImportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OtherEquipmentImportController extends Controller
{
    public function store(
        ImportInventoryFileRequest $request,
        OtherEquipmentCatalogImportService $importService,
    ): JsonResponse {
        return response()->json([
            'message' => 'Equipment catalog imported successfully.',
            'summary' => $importService->import($request->file('file'), $request->user()),
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
                'Technical-Vocational Tool Kit',
                'TVL',
                '',
                '',
                'Tool kit for TVL workshop instruction.',
                '',
                'Equipment set used for technical-vocational livelihood classes.',
                '1',
            ],
        ];

        return $this->downloadCsv($rows, 'other-equipment-catalog-import-template.csv');
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
