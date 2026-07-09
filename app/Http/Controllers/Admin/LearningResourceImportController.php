<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportInventoryFileRequest;
use App\Services\ResourceTitleCatalogImportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LearningResourceImportController extends Controller
{
    public function store(
        ImportInventoryFileRequest $request,
        ResourceTitleCatalogImportService $importService,
    ): JsonResponse {
        return response()->json([
            'message' => 'Learning resource catalog imported successfully.',
            'summary' => $importService->import($request->file('file'), $request->user()),
        ]);
    }

    public function downloadTemplate(): StreamedResponse
    {
        $rows = [
            [
                'resource_type',
                'grade_level',
                'title',
                'author',
                'publisher',
                'language',
                'subject',
                'volume',
                'edition',
                'copyright_year',
                'pages',
                'isbn',
                'description',
                'media_url',
                'is_active',
            ],
            [
                'Book',
                'Grade 7',
                'Science Learner Material',
                'DepEd',
                'Department of Education',
                'English',
                'Science',
                '',
                '1st',
                '2026',
                '240',
                '',
                'Catalog entry for Grade 7 Science',
                '',
                '1',
            ],
        ];

        return $this->downloadCsv($rows, 'learning-resource-catalog-import-template.csv');
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
