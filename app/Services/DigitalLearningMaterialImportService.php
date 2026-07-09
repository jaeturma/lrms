<?php

namespace App\Services;

use App\Models\DigitalLearningMaterial;
use App\Support\SpreadsheetRows;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class DigitalLearningMaterialImportService
{
    public function __construct(private SpreadsheetRows $spreadsheetRows) {}

    /**
     * @return array{total_rows: int, imported: int, updated: int, skipped: int, errors: array<int, array{row: int, message: string}>}
     */
    public function import(UploadedFile $file): array
    {
        $spreadsheet = $this->spreadsheetRows->read($file);
        $this->ensureRequiredColumns($spreadsheet['headers'], ['name', 'category', 'type']);

        $summary = [
            'total_rows' => 0,
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($spreadsheet['rows'] as $index => $row) {
            if ($this->isBlankRow($row)) {
                continue;
            }

            $summary['total_rows']++;

            try {
                $summary[$this->importRow($row)]++;
            } catch (\Throwable $exception) {
                $summary['skipped']++;
                $summary['errors'][] = [
                    'row' => $index + 2,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return $summary;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function importRow(array $row): string
    {
        $name = trim((string) ($row['name'] ?? ''));
        $category = trim((string) ($row['category'] ?? ''));
        $type = trim((string) ($row['type'] ?? ''));

        if ($name === '') {
            throw new \RuntimeException('Name is required.');
        }

        if (! in_array($category, DigitalLearningMaterial::CATEGORIES, true)) {
            throw new \RuntimeException('Category is invalid.');
        }

        if (! in_array($type, DigitalLearningMaterial::TYPES, true)) {
            throw new \RuntimeException('Type is invalid.');
        }

        $attributes = [
            'name' => $name,
            'category' => $category,
            'type' => $type,
            'publisher' => $this->nullable($row['publisher'] ?? ''),
            'link' => $this->nullable($row['link'] ?? ''),
            'description' => $this->nullable($row['description'] ?? ''),
            'quality_assured' => $this->boolean($row['quality_assured'] ?? '0'),
            'is_active' => $this->boolean($row['is_active'] ?? '1'),
        ];

        $material = DigitalLearningMaterial::query()
            ->where('name', $name)
            ->where('type', $type)
            ->first();

        if ($material) {
            $material->update($attributes);

            return 'updated';
        }

        DigitalLearningMaterial::query()->create($attributes);

        return 'imported';
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string>  $requiredColumns
     */
    private function ensureRequiredColumns(array $headers, array $requiredColumns): void
    {
        foreach ($requiredColumns as $requiredColumn) {
            if (! in_array($requiredColumn, $headers, true)) {
                throw ValidationException::withMessages([
                    'file' => "Missing required column: {$requiredColumn}",
                ]);
            }
        }
    }

    /**
     * @param  array<string, string>  $row
     */
    private function isBlankRow(array $row): bool
    {
        return collect($row)->every(fn (string $value): bool => trim($value) === '');
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function boolean(string $value): bool
    {
        return ! in_array(strtolower(trim($value)), ['0', 'false', 'no', 'inactive'], true);
    }
}
