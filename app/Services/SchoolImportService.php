<?php

namespace App\Services;

use App\Models\Barangay;
use App\Models\District;
use App\Models\Municipality;
use App\Models\School;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class SchoolImportService
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
        $requiredColumns = ['municipality', 'district', 'barangay', 'school_id', 'school_name'];

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
                $schoolId = trim((string) ($row[$columnIndexes['school_id']] ?? ''));
                $schoolName = trim((string) ($row[$columnIndexes['school_name']] ?? ''));

                if ($schoolId === '' || $schoolName === '') {
                    $summary['skipped']++;
                    $summary['errors'][] = [
                        'row' => $rowNumber,
                        'message' => 'School ID and School Name are required.',
                    ];

                    continue;
                }

                if (School::withTrashed()->where('school_id', $schoolId)->exists()) {
                    $summary['skipped']++;

                    continue;
                }

                $municipalityId = null;
                $districtId = null;

                if ($municipalityName !== '') {
                    $municipality = Municipality::firstOrCreate(['name' => $municipalityName]);
                    $municipalityId = $municipality->id;

                    if ($districtName !== '') {
                        $district = District::firstOrCreate([
                            'municipality_id' => $municipality->id,
                            'name' => $districtName,
                        ]);

                        $districtId = $district->id;
                    }
                }

                $barangayId = null;

                if ($barangayName !== '' && $municipalityId !== null) {
                    $barangay = Barangay::firstOrCreate([
                        'municipality_id' => $municipalityId,
                        'name' => $barangayName,
                    ]);

                    $barangayId = $barangay->id;
                }

                School::create([
                    'municipality_id' => $municipalityId,
                    'district_id' => $districtId,
                    'barangay_id' => $barangayId,
                    'school_id' => $schoolId,
                    'school_name' => $schoolName,
                ]);

                $summary['imported']++;
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
