<?php

namespace App\Services;

use App\Models\GradeLevel;
use App\Models\LearningResourceType;
use App\Models\ResourceTitle;
use App\Support\SpreadsheetRows;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ResourceTitleCatalogImportService
{
    public function __construct(private SpreadsheetRows $spreadsheetRows) {}

    /**
     * @return array{total_rows: int, imported: int, updated: int, skipped: int, errors: array<int, array{row: int, message: string}>}
     */
    public function import(UploadedFile $file): array
    {
        $spreadsheet = $this->spreadsheetRows->read($file);
        $this->ensureRequiredColumns($spreadsheet['headers'], ['resource_type', 'title']);

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
        $resourceType = LearningResourceType::query()
            ->where('is_active', true)
            ->where('name', trim((string) ($row['resource_type'] ?? '')))
            ->first();

        if (! $resourceType) {
            throw new \RuntimeException('Learning resource type was not found.');
        }

        $title = trim((string) ($row['title'] ?? ''));

        if ($title === '') {
            throw new \RuntimeException('Title is required.');
        }

        $gradeLevel = $this->gradeLevel($row['grade_level'] ?? '');
        $publisher = $this->nullable($row['publisher'] ?? '');
        $isbn = $this->nullable($row['isbn'] ?? '');

        $attributes = [
            'learning_resource_type_id' => $resourceType->id,
            'grade_level_id' => $gradeLevel?->id,
            'title' => $title,
            'author' => $this->nullable($row['author'] ?? ''),
            'publisher' => $publisher,
            'language' => $this->nullable($row['language'] ?? ''),
            'subject' => $this->nullable($row['subject'] ?? ''),
            'volume' => $this->nullable($row['volume'] ?? ''),
            'edition' => $this->nullable($row['edition'] ?? ''),
            'copyright_year' => $this->nullableInteger($row['copyright_year'] ?? '', 'copyright_year'),
            'pages' => $this->nullableInteger($row['pages'] ?? '', 'pages'),
            'isbn' => $isbn,
            'description' => $this->nullable($row['description'] ?? ''),
            'media_url' => $this->nullable($row['media_url'] ?? ''),
            'is_active' => $this->boolean($row['is_active'] ?? '1'),
        ];

        $resourceTitle = ResourceTitle::query()
            ->where('learning_resource_type_id', $resourceType->id)
            ->where('title', $title)
            ->when($isbn, fn ($query) => $query->where('isbn', $isbn))
            ->when(! $isbn, fn ($query) => $query->where('publisher', $publisher))
            ->first();

        if ($resourceTitle) {
            $resourceTitle->update($attributes);

            return 'updated';
        }

        ResourceTitle::query()->create($attributes);

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

    private function gradeLevel(string $name): ?GradeLevel
    {
        if (trim($name) === '') {
            return null;
        }

        $gradeLevel = GradeLevel::query()->where('name', trim($name))->first();

        if (! $gradeLevel) {
            throw new \RuntimeException('Grade level was not found.');
        }

        return $gradeLevel;
    }

    private function nullableInteger(string $value, string $field): ?int
    {
        if (trim($value) === '') {
            return null;
        }

        if (! ctype_digit($value)) {
            throw new \RuntimeException("{$field} must be a whole number.");
        }

        return (int) $value;
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
