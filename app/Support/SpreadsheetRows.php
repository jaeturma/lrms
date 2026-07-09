<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use SimpleXMLElement;
use ZipArchive;

class SpreadsheetRows
{
    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, string>>}
     */
    public function read(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'csv', 'txt' => $this->readCsv($file),
            'xlsx' => $this->readXlsx($file),
            default => throw ValidationException::withMessages([
                'file' => 'Upload a CSV or Excel .xlsx file.',
            ]),
        };
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, string>>}
     */
    private function readCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if (! $handle) {
            throw ValidationException::withMessages(['file' => 'Unable to open uploaded file.']);
        }

        $header = fgetcsv($handle);
        $headers = $this->normalizeHeaders($header ?: []);
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $this->combineRow($headers, $row);
        }

        fclose($handle);

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<string, string>>}
     */
    private function readXlsx(UploadedFile $file): array
    {
        $archive = new ZipArchive;

        if ($archive->open($file->getRealPath()) !== true) {
            throw ValidationException::withMessages(['file' => 'Unable to open uploaded Excel file.']);
        }

        $sharedStrings = $this->sharedStrings($archive);
        $worksheetXml = $archive->getFromName('xl/worksheets/sheet1.xml');
        $archive->close();

        if ($worksheetXml === false) {
            throw ValidationException::withMessages(['file' => 'The Excel file must include a first worksheet.']);
        }

        $worksheet = simplexml_load_string($worksheetXml);

        if (! $worksheet) {
            throw ValidationException::withMessages(['file' => 'Unable to read the Excel worksheet.']);
        }

        $matrix = [];

        foreach ($worksheet->sheetData->row as $row) {
            $values = [];

            foreach ($row->c as $cell) {
                $reference = (string) $cell['r'];
                $values[$this->columnIndex($reference)] = $this->cellValue($cell, $sharedStrings);
            }

            if ($values === []) {
                continue;
            }

            ksort($values);
            $matrix[] = $values;
        }

        if ($matrix === []) {
            return ['headers' => [], 'rows' => []];
        }

        $headers = $this->normalizeHeaders($matrix[0]);
        $rows = collect(array_slice($matrix, 1))
            ->map(fn (array $row): array => $this->combineRow($headers, $row))
            ->values()
            ->all();

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * @return array<int, string>
     */
    private function sharedStrings(ZipArchive $archive): array
    {
        $xml = $archive->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $sharedStrings = simplexml_load_string($xml);

        if (! $sharedStrings) {
            return [];
        }

        $values = [];

        foreach ($sharedStrings->si as $item) {
            if (isset($item->t)) {
                $values[] = (string) $item->t;

                continue;
            }

            $values[] = collect($item->r)
                ->map(fn ($run): string => (string) $run->t)
                ->implode('');
        }

        return $values;
    }

    /**
     * @param  array<int|string, mixed>  $headers
     * @param  array<int|string, mixed>  $row
     * @return array<string, string>
     */
    private function combineRow(array $headers, array $row): array
    {
        $combined = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $combined[$header] = trim((string) ($row[$index] ?? ''));
        }

        return $combined;
    }

    /**
     * @param  array<int|string, mixed>  $headers
     * @return array<int, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        return Collection::make($headers)
            ->map(fn (mixed $header): string => trim(strtolower((string) $header)))
            ->map(fn (string $header): string => preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header)
            ->map(fn (string $header): string => preg_replace('/[^a-z0-9]+/', '_', $header) ?? $header)
            ->map(fn (string $header): string => trim($header, '_'))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $sharedStrings
     */
    private function cellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];

        if ($type === 'inlineStr') {
            return trim((string) ($cell->is->t ?? ''));
        }

        $value = trim((string) ($cell->v ?? ''));

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        return $value;
    }

    private function columnIndex(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $matches);
        $letters = strtoupper($matches[0] ?? 'A');
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }
}
