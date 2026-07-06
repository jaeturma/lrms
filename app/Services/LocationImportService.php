<?php

namespace App\Services;

use App\Models\Barangay;
use App\Models\District;
use App\Models\Municipality;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class LocationImportService
{
    /**
     * @return array{total_rows: int, imported: int, skipped: int, errors: array<int, array{row: int, message: string}>}
     */
    public function import(UploadedFile $csvFile): array
    {
        $summary = [
            'total_rows' => 0,
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $handle = fopen($csvFile->getRealPath(), 'rb');

        if (! $handle) {
            throw ValidationException::withMessages(['csv' => 'Unable to open uploaded CSV file.']);
        }

        $header = fgetcsv($handle);
        $columns = collect($header ?? [])->map(fn (mixed $column): string => strtolower(trim((string) $column)))->values();
        $requiredColumns = ['municipality', 'district', 'barangay'];

        foreach ($requiredColumns as $requiredColumn) {
            if (! $columns->contains($requiredColumn)) {
                fclose($handle);

                throw ValidationException::withMessages([
                    'csv' => "Missing required CSV column: {$requiredColumn}",
                ]);
            }
        }

        $columnIndexes = collect($requiredColumns)
            ->mapWithKeys(fn (string $column): array => [$column => (int) $columns->search($column)])
            ->all();

        while (($row = fgetcsv($handle)) !== false) {
            $summary['total_rows']++;
            $rowNumber = $summary['total_rows'] + 1;

            try {
                $municipalityName = trim((string) ($row[$columnIndexes['municipality']] ?? ''));
                $districtName = trim((string) ($row[$columnIndexes['district']] ?? ''));
                $barangayName = trim((string) ($row[$columnIndexes['barangay']] ?? ''));

                if ($municipalityName === '') {
                    $summary['skipped']++;
                    $summary['errors'][] = [
                        'row' => $rowNumber,
                        'message' => 'Municipality is required.',
                    ];

                    continue;
                }

                if ($districtName === '') {
                    $summary['skipped']++;
                    $summary['errors'][] = [
                        'row' => $rowNumber,
                        'message' => 'District is required.',
                    ];

                    continue;
                }

                $municipality = Municipality::firstOrCreate(['name' => $municipalityName]);
                $created = $municipality->wasRecentlyCreated;

                $district = District::firstOrCreate([
                    'municipality_id' => $municipality->id,
                    'name' => $districtName,
                ]);
                $created = $created || $district->wasRecentlyCreated;

                if ($barangayName === '') {
                    $summary[$created ? 'imported' : 'skipped']++;

                    continue;
                }

                $barangay = Barangay::firstOrCreate([
                    'municipality_id' => $municipality->id,
                    'name' => $barangayName,
                ]);

                $summary[$created || $barangay->wasRecentlyCreated ? 'imported' : 'skipped']++;
            } catch (\Throwable $exception) {
                $summary['skipped']++;
                $summary['errors'][] = [
                    'row' => $rowNumber,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        fclose($handle);

        return $summary;
    }
}
