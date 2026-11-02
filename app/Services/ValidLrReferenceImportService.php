<?php

namespace App\Services;

use App\Models\ValidLrAllocation;
use App\Models\ValidLrLrmisCount;
use App\Models\ValidLrSchool;
use App\Models\ValidLrSmeItem;
use App\Models\ValidLrTitle;
use App\Models\ValidLrTvlItem;
use App\Models\ValidLrTvlProgram;
use App\Support\ValidLrTemplate;
use App\Support\XlsxStreamReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Imports the workbooks behind the VALID-LR monitoring tool:
 *
 * - `_reference_data.xlsx`  → schools of one division, LR allocations, regional titles/publishers
 * - `LRMIS_ENCODED.xlsx`    → LRs each school encoded in LRMIS
 * - `masterlist lte.xlsx`   → SME delivery years per key stage
 * - `VALID-LR_Monitoring_Tool.xlsx` → SME/TVL package item lists and TVL procurement status
 *
 * Only schools already loaded by the reference-data import (one division)
 * are kept from the per-school workbooks.
 */
class ValidLrReferenceImportService
{
    public const TYPE_REFERENCE_DATA = 'reference_data';

    public const TYPE_LRMIS_ENCODED = 'lrmis_encoded';

    public const TYPE_SME_MASTERLIST = 'sme_masterlist';

    public const TYPE_MONITORING_TOOL = 'monitoring_tool';

    public const TYPES = [
        self::TYPE_REFERENCE_DATA,
        self::TYPE_LRMIS_ENCODED,
        self::TYPE_SME_MASTERLIST,
        self::TYPE_MONITORING_TOOL,
    ];

    private const INSERT_CHUNK = 500;

    /**
     * @return array<string, int|string>
     */
    public function import(string $type, string $path, ?string $division = null): array
    {
        @set_time_limit(0);

        return match ($type) {
            self::TYPE_REFERENCE_DATA => $this->importReferenceData($path, (string) $division),
            self::TYPE_LRMIS_ENCODED => $this->importLrmisEncoded($path),
            self::TYPE_SME_MASTERLIST => $this->importSmeMasterlist($path),
            self::TYPE_MONITORING_TOOL => $this->importMonitoringTool($path),
            default => throw ValidationException::withMessages(['type' => 'Unknown VALID-LR import type.']),
        };
    }

    /**
     * @return array{division: string, schools_created: int, schools_updated: int, allocations: int, titles: int, rows_outside_division: int}
     */
    public function importReferenceData(string $path, string $division): array
    {
        $division = trim($division);

        if ($division === '') {
            throw ValidationException::withMessages(['division' => 'Enter the division to import.']);
        }

        $reader = XlsxStreamReader::open($path);
        $this->ensureSheets($reader, ['allocation']);

        $summary = [
            'division' => $division,
            'schools_created' => 0,
            'schools_updated' => 0,
            'allocations' => 0,
            'titles' => 0,
            'rows_outside_division' => 0,
        ];

        $profileColumns = [];
        $allocationColumns = [];
        $matchedDivision = null;

        DB::transaction(function () use ($reader, $division, &$summary, &$profileColumns, &$allocationColumns, &$matchedDivision): void {
            foreach ($reader->rows('allocation') as $rowNumber => $row) {
                if ($profileColumns === []) {
                    [$profileColumns, $allocationColumns] = $this->allocationHeaderMap($row);

                    continue;
                }

                $rowDivision = trim($row[$profileColumns['division']] ?? '');

                if (strcasecmp($rowDivision, $division) !== 0) {
                    $summary['rows_outside_division']++;

                    continue;
                }

                $matchedDivision ??= $rowDivision;
                $beisSchoolId = $this->normalizeId($row[$profileColumns['beis_school_id']] ?? '');

                if ($beisSchoolId === '') {
                    continue;
                }

                $attributes = ['school_name' => $row[$profileColumns['school_name']] ?? $beisSchoolId];

                foreach ($profileColumns as $attribute => $columnIndex) {
                    if (! in_array($attribute, ['beis_school_id', 'school_name'], true)) {
                        $attributes[$attribute] = $this->nullable($row[$columnIndex] ?? '');
                    }
                }

                $school = ValidLrSchool::query()->firstOrNew(['beis_school_id' => $beisSchoolId]);
                $summary[$school->exists ? 'schools_updated' : 'schools_created']++;
                $school->fill($attributes)->save();

                $school->allocations()->delete();
                $records = [];

                foreach ($allocationColumns as $columnIndex => [$gradeLevel, $learningArea]) {
                    $quantity = $this->integer($row[$columnIndex] ?? '');

                    if ($quantity === null) {
                        continue;
                    }

                    $records[] = [
                        'valid_lr_school_id' => $school->id,
                        'grade_level' => $gradeLevel,
                        'learning_area' => $learningArea,
                        'quantity' => $quantity,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                foreach (array_chunk($records, self::INSERT_CHUNK) as $chunk) {
                    ValidLrAllocation::query()->insert($chunk);
                }

                $summary['allocations'] += count($records);
            }

            foreach (['LRD_Elem', 'LRD_HS'] as $sheetName) {
                if ($reader->hasSheet($sheetName)) {
                    $summary['titles'] += $this->importTitles($reader, $sheetName);
                }
            }
        });

        if ($matchedDivision === null) {
            throw ValidationException::withMessages([
                'division' => "No schools were found for the \"{$division}\" division in this file.",
            ]);
        }

        $summary['division'] = $matchedDivision;

        return $summary;
    }

    /**
     * @return array{rows_read: int, rows_matched: int, schools_matched: int, records: int}
     */
    public function importLrmisEncoded(string $path): array
    {
        $reader = XlsxStreamReader::open($path);
        $sheetName = $reader->hasSheet('LRMIS_ENCODED') ? 'LRMIS_ENCODED' : $reader->sheetNames()[0];
        $schoolIds = $this->knownSchoolIds();

        $summary = ['rows_read' => 0, 'rows_matched' => 0, 'schools_matched' => 0, 'records' => 0];
        $columns = null;
        $totals = [];

        foreach ($reader->rows($sheetName) as $row) {
            if ($columns === null) {
                $columns = $this->requireColumns($row, ['grade_level', 'subject_name', 'total_lr', 'beis_id']);

                continue;
            }

            $summary['rows_read']++;
            $schoolId = $schoolIds[$this->normalizeId($row[$columns['beis_id']] ?? '')] ?? null;
            $total = $this->integer($row[$columns['total_lr']] ?? '');

            if ($schoolId === null || $total === null) {
                continue;
            }

            $summary['rows_matched']++;
            $gradeLevel = trim($row[$columns['grade_level']] ?? '');
            $learningArea = ValidLrTemplate::normalizeLearningArea($row[$columns['subject_name']] ?? '');
            $totals[$schoolId][$gradeLevel][$learningArea] = ($totals[$schoolId][$gradeLevel][$learningArea] ?? 0) + $total;
        }

        DB::transaction(function () use ($totals, &$summary): void {
            ValidLrLrmisCount::query()->whereIn('valid_lr_school_id', array_keys($totals))->delete();
            $records = [];

            foreach ($totals as $schoolId => $grades) {
                foreach ($grades as $gradeLevel => $areas) {
                    foreach ($areas as $learningArea => $total) {
                        $records[] = [
                            'valid_lr_school_id' => $schoolId,
                            'grade_level' => $gradeLevel,
                            'learning_area' => $learningArea,
                            'total' => $total,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            foreach (array_chunk($records, self::INSERT_CHUNK) as $chunk) {
                ValidLrLrmisCount::query()->insert($chunk);
            }

            $summary['records'] = count($records);
        });

        $summary['schools_matched'] = count($totals);

        return $summary;
    }

    /**
     * @return array{rows_read: int, schools_matched: int, deliveries: int}
     */
    public function importSmeMasterlist(string $path): array
    {
        $reader = XlsxStreamReader::open($path);
        $sheetName = $this->findSheetWithHeader($reader, 'BEIS School ID', '/^SME \d{4}-/');
        $schoolIds = $this->knownSchoolIds();

        $summary = ['rows_read' => 0, 'schools_matched' => 0, 'deliveries' => 0];
        $beisColumn = null;
        $deliveryColumns = [];

        DB::transaction(function () use ($reader, $sheetName, $schoolIds, &$summary, &$beisColumn, &$deliveryColumns): void {
            foreach ($reader->rows($sheetName) as $row) {
                if ($beisColumn === null) {
                    foreach ($row as $columnIndex => $header) {
                        if ($header === 'BEIS School ID') {
                            $beisColumn = $columnIndex;
                        } elseif (preg_match('/^SME (\d{4})-(.+)$/', $header, $matches) === 1) {
                            $deliveryColumns[$columnIndex] = ['year' => (int) $matches[1], 'key_stage' => trim($matches[2])];
                        }
                    }

                    continue;
                }

                $summary['rows_read']++;
                $schoolId = $schoolIds[$this->normalizeId($row[$beisColumn] ?? '')] ?? null;

                if ($schoolId === null) {
                    continue;
                }

                $deliveries = [];

                foreach ($deliveryColumns as $columnIndex => $delivery) {
                    if (($this->integer($row[$columnIndex] ?? '') ?? 0) > 0) {
                        $deliveries[] = $delivery;
                    }
                }

                ValidLrSchool::query()->whereKey($schoolId)->update(['sme_deliveries' => json_encode($deliveries)]);
                $summary['schools_matched']++;
                $summary['deliveries'] += count($deliveries);
            }
        });

        return $summary;
    }

    /**
     * @return array{sme_central_office_items: int, sme_downloaded_items: int, tvl_items: int, tvl_programs: int}
     */
    public function importMonitoringTool(string $path): array
    {
        $reader = XlsxStreamReader::open($path);
        $this->ensureSheets($reader, ['SME CO Allocation', 'SME DL Allocation', 'TVL', 'TVL Procurement Status']);
        $schoolIds = $this->knownSchoolIds();

        $summary = ['sme_central_office_items' => 0, 'sme_downloaded_items' => 0, 'tvl_items' => 0, 'tvl_programs' => 0];

        DB::transaction(function () use ($reader, $schoolIds, &$summary): void {
            ValidLrSmeItem::query()->delete();
            $summary['sme_central_office_items'] = $this->importSmeItems($reader, 'SME CO Allocation', ValidLrSmeItem::SOURCE_CENTRAL_OFFICE);
            $summary['sme_downloaded_items'] = $this->importSmeItems($reader, 'SME DL Allocation', ValidLrSmeItem::SOURCE_DOWNLOADED);

            ValidLrTvlItem::query()->delete();
            $summary['tvl_items'] = $this->importTvlItems($reader);
            $summary['tvl_programs'] = $this->importTvlPrograms($reader, $schoolIds);
        });

        return $summary;
    }

    private function importTitles(XlsxStreamReader $reader, string $sheetName): int
    {
        $titleColumns = [];
        $publisherColumns = [];
        $entries = [];
        $lastKey = null;

        foreach ($reader->rows($sheetName) as $rowNumber => $row) {
            if ($titleColumns === [] && $publisherColumns === []) {
                foreach ($row as $columnIndex => $header) {
                    if ($columnIndex < 2) {
                        continue;
                    }

                    if (in_array($header, $titleColumns, true)) {
                        $publisherColumns[$columnIndex] = $header;
                    } else {
                        $titleColumns[$columnIndex] = $header;
                    }
                }

                continue;
            }

            $gradeLevel = trim($row[0] ?? '');
            $learningArea = trim($row[1] ?? '');

            if ($gradeLevel !== '' && $learningArea !== '') {
                $lastKey = $gradeLevel."\0".$learningArea;
                $entries[$lastKey] = ['grade_level' => $gradeLevel, 'learning_area' => $learningArea, 'regions' => []];
            } elseif ($gradeLevel !== '' || $learningArea !== '' || $lastKey === null) {
                $lastKey = null;

                continue;
            }

            // Rows with blank grade/area continue the previous row's wrapped title or publisher.
            foreach (['title' => $titleColumns, 'publisher' => $publisherColumns] as $field => $columns) {
                foreach ($columns as $columnIndex => $region) {
                    $value = trim($row[$columnIndex] ?? '');

                    if ($value !== '') {
                        $current = $entries[$lastKey]['regions'][$region][$field] ?? '';
                        $entries[$lastKey]['regions'][$region][$field] = trim($current.' '.$value);
                    }
                }
            }
        }

        $count = 0;

        foreach ($entries as $entry) {
            foreach ($entry['regions'] as $region => $values) {
                ValidLrTitle::query()->updateOrCreate(
                    ['region' => $region, 'grade_level' => $entry['grade_level'], 'learning_area' => $entry['learning_area']],
                    ['title' => isset($values['title']) ? mb_substr($values['title'], 0, 500) : null, 'publisher' => isset($values['publisher']) ? mb_substr($values['publisher'], 0, 255) : null],
                );
                $count++;
            }
        }

        return $count;
    }

    private function importSmeItems(XlsxStreamReader $reader, string $sheetName, string $source): int
    {
        $records = [];
        $seen = [];

        foreach ($reader->rows($sheetName) as $rowNumber => $row) {
            if ($rowNumber === 1) {
                continue;
            }

            $itemName = trim($row[1] ?? '');
            $keyStage = trim($row[3] ?? '');

            if ($itemName === '' || $keyStage === '') {
                continue;
            }

            $record = [
                'source' => $source,
                'group_label' => $this->nullable($row[0] ?? ''),
                'item_name' => mb_substr($itemName, 0, 1000),
                'unit' => $this->nullable($row[2] ?? ''),
                'key_stage' => $keyStage,
                'quantity' => $this->integer($row[4] ?? '') ?? 0,
            ];

            $key = implode("\0", $record);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $records[] = $record + ['created_at' => now(), 'updated_at' => now()];
        }

        foreach (array_chunk($records, self::INSERT_CHUNK) as $chunk) {
            ValidLrSmeItem::query()->insert($chunk);
        }

        return count($records);
    }

    private function importTvlItems(XlsxStreamReader $reader): int
    {
        $records = [];
        $headerFound = false;

        foreach ($reader->rows('TVL') as $row) {
            if (! $headerFound) {
                $headerFound = trim($row[0] ?? '') === 'Item Name';

                continue;
            }

            $itemName = trim($row[0] ?? '');
            $specialization = trim($row[3] ?? '');

            if ($itemName === '' || $specialization === '') {
                continue;
            }

            $procurementSource = match (true) {
                ($this->integer($row[1] ?? '') ?? 0) > 0 => ValidLrSmeItem::SOURCE_CENTRAL_OFFICE,
                ($this->integer($row[2] ?? '') ?? 0) > 0 => ValidLrSmeItem::SOURCE_DOWNLOADED,
                default => null,
            };

            $records[] = [
                'specialization' => $specialization,
                'item_name' => mb_substr($itemName, 0, 1000),
                'procurement_source' => $procurementSource,
                'quantity' => $this->integer($row[4] ?? '') ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($records, self::INSERT_CHUNK) as $chunk) {
            ValidLrTvlItem::query()->insert($chunk);
        }

        return count($records);
    }

    /**
     * @param  array<string, int>  $schoolIds
     */
    private function importTvlPrograms(XlsxStreamReader $reader, array $schoolIds): int
    {
        $yearColumns = [];
        $statusColumn = null;
        $programs = [];

        foreach ($reader->rows('TVL Procurement Status') as $rowNumber => $row) {
            if ($rowNumber === 1) {
                foreach ($row as $columnIndex => $header) {
                    if (preg_match('/^\d{4}$/', $header) === 1) {
                        $yearColumns[$columnIndex] = (int) $header;
                    } elseif (strcasecmp($header, 'STATUS') === 0) {
                        $statusColumn = $columnIndex;
                    }
                }

                continue;
            }

            $schoolId = $schoolIds[$this->normalizeId($row[0] ?? '')] ?? null;
            $specialization = trim($row[2] ?? '');

            if ($schoolId === null || $specialization === '') {
                continue;
            }

            $years = [];

            foreach ($yearColumns as $columnIndex => $year) {
                if (($this->integer($row[$columnIndex] ?? '') ?? 0) > 0) {
                    $years[] = $year;
                }
            }

            $programs[$schoolId][$specialization] = [
                'valid_lr_school_id' => $schoolId,
                'specialization' => $specialization,
                'delivery_years' => json_encode($years),
                'status' => $statusColumn !== null ? $this->nullable($row[$statusColumn] ?? '') : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        ValidLrTvlProgram::query()->whereIn('valid_lr_school_id', array_values($schoolIds))->delete();
        $records = array_merge(...array_map('array_values', array_values($programs ?: [[]])));

        foreach (array_chunk($records, self::INSERT_CHUNK) as $chunk) {
            ValidLrTvlProgram::query()->insert($chunk);
        }

        return count($records);
    }

    /**
     * @param  array<int, string>  $headerRow
     * @return array{0: array<string, int>, 1: array<int, array{0: string, 1: string}>}
     */
    private function allocationHeaderMap(array $headerRow): array
    {
        $profileHeaders = [
            'Region' => 'region',
            'Division' => 'division',
            'District' => 'district',
            'beis_school_id' => 'beis_school_id',
            'School Visited' => 'school_name',
            'street_address' => 'street_address',
            'Mother School ID' => 'mother_school_id',
            'Province' => 'province',
            'Municipality' => 'municipality',
            'Leg_district' => 'legislative_district',
            'Barangay' => 'barangay',
            'Sector' => 'sector',
            'School_type' => 'school_type',
            'Modified Curricular Offering Classification' => 'curricular_offering',
        ];

        $profileColumns = [];
        $allocationColumns = [];

        foreach ($headerRow as $columnIndex => $header) {
            if (isset($profileHeaders[$header])) {
                $profileColumns[$profileHeaders[$header]] ??= $columnIndex;

                continue;
            }

            $parsed = ValidLrTemplate::parseAllocationHeader($header);

            if ($parsed !== null) {
                $allocationColumns[$columnIndex] = $parsed;
            }
        }

        foreach (['division', 'beis_school_id', 'school_name'] as $required) {
            if (! isset($profileColumns[$required])) {
                throw ValidationException::withMessages([
                    'file' => 'The allocation sheet is missing the '.array_search($required, $profileHeaders, true).' column.',
                ]);
            }
        }

        return [$profileColumns, $allocationColumns];
    }

    /**
     * @param  array<int, string>  $headerRow
     * @param  array<int, string>  $required
     * @return array<string, int>
     */
    private function requireColumns(array $headerRow, array $required): array
    {
        $columns = [];

        foreach ($headerRow as $columnIndex => $header) {
            $columns[strtolower(trim($header))] ??= $columnIndex;
        }

        foreach ($required as $column) {
            if (! isset($columns[$column])) {
                throw ValidationException::withMessages(['file' => "Missing required column: {$column}"]);
            }
        }

        return $columns;
    }

    private function findSheetWithHeader(XlsxStreamReader $reader, string $firstHeader, string $pattern): string
    {
        foreach ($reader->sheetNames() as $sheetName) {
            foreach ($reader->rows($sheetName) as $row) {
                if (in_array($firstHeader, $row, true) && preg_grep($pattern, $row) !== []) {
                    return $sheetName;
                }

                break;
            }
        }

        throw ValidationException::withMessages([
            'file' => 'No sheet with a "BEIS School ID" column and "SME <year>-<key stage>" delivery columns was found.',
        ]);
    }

    /**
     * @param  array<int, string>  $sheetNames
     */
    private function ensureSheets(XlsxStreamReader $reader, array $sheetNames): void
    {
        foreach ($sheetNames as $sheetName) {
            if (! $reader->hasSheet($sheetName)) {
                throw ValidationException::withMessages(['file' => "The workbook has no \"{$sheetName}\" sheet."]);
            }
        }
    }

    /**
     * @return array<string, int> BEIS school ID => valid_lr_schools.id
     */
    private function knownSchoolIds(): array
    {
        $schoolIds = ValidLrSchool::query()->pluck('id', 'beis_school_id')->all();

        if ($schoolIds === []) {
            throw ValidationException::withMessages([
                'file' => 'Import the reference data (_reference_data.xlsx) for your division first.',
            ]);
        }

        return $schoolIds;
    }

    /**
     * Excel stores numeric IDs as floats ("113505.0").
     */
    private function normalizeId(string $value): string
    {
        $value = trim($value);

        return preg_match('/^\d+(\.0+)?$/', $value) === 1 ? (string) (int) $value : $value;
    }

    private function integer(string $value): ?int
    {
        $value = trim($value);

        return is_numeric($value) ? (int) round((float) $value) : null;
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, 255);
    }
}
