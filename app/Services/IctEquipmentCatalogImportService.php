<?php

namespace App\Services;

use App\Models\IctEquipment;
use App\Models\IctEquipmentCatalogItem;
use App\Support\SpreadsheetRows;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class IctEquipmentCatalogImportService
{
    public function __construct(private SpreadsheetRows $spreadsheetRows) {}

    /**
     * @return array{total_rows: int, imported: int, updated: int, skipped: int, errors: array<int, array{row: int, message: string}>}
     */
    public function import(UploadedFile $file): array
    {
        $spreadsheet = $this->spreadsheetRows->read($file);
        $this->ensureRequiredColumns($spreadsheet['headers'], ['item_name', 'category']);

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
        $itemName = trim((string) ($row['item_name'] ?? ''));
        $category = trim((string) ($row['category'] ?? ''));

        if ($itemName === '') {
            throw new \RuntimeException('Item name is required.');
        }

        if (! in_array($category, IctEquipment::CATEGORIES, true)) {
            throw new \RuntimeException('Equipment category is invalid.');
        }

        $attributes = [
            'item_name' => $itemName,
            'category' => $category,
            'brand' => $this->nullable($row['brand'] ?? ''),
            'model' => $this->nullable($row['model'] ?? ''),
            'specifications' => $this->nullable($row['specifications'] ?? ''),
            'manufacturer' => $this->nullable($row['manufacturer'] ?? ''),
            'description' => $this->nullable($row['description'] ?? ''),
            'is_active' => $this->boolean($row['is_active'] ?? '1'),
        ];

        $catalogItem = IctEquipmentCatalogItem::query()
            ->where('item_name', $itemName)
            ->where('category', $category)
            ->first();

        if ($catalogItem) {
            $catalogItem->update($attributes);

            return 'updated';
        }

        IctEquipmentCatalogItem::query()->create($attributes);

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
