<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportInventoryFileRequest;
use App\Services\IctEquipmentCatalogImportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IctEquipmentImportController extends Controller
{
    public function store(
        ImportInventoryFileRequest $request,
        IctEquipmentCatalogImportService $importService,
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
                'Laptop Computer',
                'Laptop',
                'Lenovo',
                'ThinkPad',
                'Portable computer for teacher or learner use.',
                'Lenovo',
                'General-purpose laptop used for instruction.',
                '1',
            ],
        ];

        return $this->downloadCsv($rows, 'ict-equipment-catalog-import-template.csv');
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
