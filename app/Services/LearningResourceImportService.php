<?php

namespace App\Services;

use App\Models\GradeLevel;
use App\Models\LearningResource;
use App\Models\LearningResourceType;
use App\Models\School;
use App\Models\User;
use App\Support\SpreadsheetRows;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LearningResourceImportService
{
    public function __construct(
        private SpreadsheetRows $spreadsheetRows,
        private LearningResourceInventoryService $inventoryService,
    ) {}

    /**
     * @return array{total_rows: int, imported: int, updated: int, skipped: int, errors: array<int, array{row: int, message: string}>}
     */
    public function import(UploadedFile $file, ?User $user = null): array
    {
        $spreadsheet = $this->spreadsheetRows->read($file);
        $requiredColumns = ['school_id', 'resource_type', 'title', 'publisher', 'quantity_delivered'];

        $this->ensureRequiredColumns($spreadsheet['headers'], $requiredColumns);

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
            $rowNumber = $index + 2;

            try {
                $result = $this->importRow($row, $user);
                $summary[$result]++;
            } catch (\Throwable $exception) {
                $summary['skipped']++;
                $summary['errors'][] = [
                    'row' => $rowNumber,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return $summary;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function importRow(array $row, ?User $user): string
    {
        $school = School::query()
            ->where('school_id', $row['school_id'] ?? '')
            ->first();

        if (! $school) {
            throw new \RuntimeException('School ID was not found.');
        }

        $resourceType = LearningResourceType::query()
            ->where('name', $row['resource_type'] ?? '')
            ->first();

        if (! $resourceType) {
            throw new \RuntimeException('Learning resource type was not found.');
        }

        $delivered = $this->integer($row['quantity_delivered'] ?? '', 'quantity_delivered');
        $damaged = $this->integer($row['quantity_with_issue_defect'] ?? '0', 'quantity_with_issue_defect');

        if ($damaged > $delivered) {
            throw new \RuntimeException('Quantity with issue/defect cannot be greater than quantity delivered.');
        }

        $gradeLevel = $this->gradeLevel($row['grade_level'] ?? '');
        $title = trim((string) ($row['title'] ?? ''));
        $publisher = trim((string) ($row['publisher'] ?? ''));

        if ($title === '' || $publisher === '') {
            throw new \RuntimeException('Title and publisher are required.');
        }

        return DB::transaction(function () use ($school, $resourceType, $gradeLevel, $row, $title, $publisher, $delivered, $damaged, $user): string {
            $resource = LearningResource::query()
                ->where('school_id', $school->id)
                ->where('learning_resource_type_id', $resourceType->id)
                ->where('title', $title)
                ->where('publisher', $publisher)
                ->first();

            $attributes = [
                'school_id' => $school->id,
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
                'isbn' => $this->nullable($row['isbn'] ?? ''),
                'quantity_delivered' => $delivered,
                'quantity_with_issue_defect' => $damaged,
                'remarks' => $this->nullable($row['remarks'] ?? ''),
            ];

            if ($resource) {
                $previousDelivered = (int) $resource->quantity_delivered;
                $previousDamaged = min((int) $resource->quantity_with_issue_defect, $previousDelivered);
                $resource->update($attributes);
                $this->inventoryService->applyEncodingUpdate($resource, $previousDelivered, $previousDamaged, $user);

                return 'updated';
            }

            $resource = LearningResource::query()->create($attributes);
            $this->inventoryService->initialize($resource, $user);

            return 'imported';
        });
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

        $gradeLevel = GradeLevel::query()->where('name', $name)->first();

        if (! $gradeLevel) {
            throw new \RuntimeException('Grade level was not found.');
        }

        return $gradeLevel;
    }

    private function integer(string $value, string $field): int
    {
        if (! ctype_digit($value)) {
            throw new \RuntimeException("{$field} must be a whole number.");
        }

        return (int) $value;
    }

    private function nullableInteger(string $value, string $field): ?int
    {
        if (trim($value) === '') {
            return null;
        }

        return $this->integer($value, $field);
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
