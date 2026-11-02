<?php

namespace App\Support;

use Generator;
use Illuminate\Validation\ValidationException;
use XMLReader;
use ZipArchive;

/**
 * Streams rows from a named worksheet of an .xlsx file without loading the
 * whole sheet into memory. Use this instead of SpreadsheetRows for large
 * multi-sheet workbooks (hundreds of thousands of rows).
 *
 * Formula cells yield their cached value, as last calculated by Excel.
 */
class XlsxStreamReader
{
    private const MAIN_NAMESPACE = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /**
     * @var array<string, string> sheet name => worksheet part path inside the zip
     */
    private array $sheetPaths = [];

    /**
     * @var array<int, string>
     */
    private array $sharedStrings = [];

    private function __construct(private string $path) {}

    public static function open(string $path): self
    {
        $reader = new self($path);
        $reader->loadWorkbook();

        return $reader;
    }

    /**
     * @return array<int, string>
     */
    public function sheetNames(): array
    {
        return array_keys($this->sheetPaths);
    }

    public function hasSheet(string $name): bool
    {
        return isset($this->sheetPaths[$name]);
    }

    /**
     * Yields each non-empty row as [1-based row number => [0-based column index => value]].
     *
     * @return Generator<int, array<int, string>>
     */
    public function rows(string $sheetName): Generator
    {
        if (! $this->hasSheet($sheetName)) {
            throw ValidationException::withMessages([
                'file' => "The workbook has no \"{$sheetName}\" sheet.",
            ]);
        }

        $reader = new XMLReader;
        $reader->open($this->zipUri($this->sheetPaths[$sheetName]));

        $moved = $reader->read();

        while ($moved) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                $moved = $reader->read();

                continue;
            }

            $rowNumber = (int) $reader->getAttribute('r');
            $node = $reader->expand();
            $values = [];

            if ($node !== false) {
                foreach ($node->childNodes as $cell) {
                    if ($cell->nodeType !== XML_ELEMENT_NODE || $cell->localName !== 'c') {
                        continue;
                    }

                    $value = $this->cellValue($cell);

                    if ($value !== '') {
                        $values[$this->columnIndex($cell->getAttribute('r'))] = $value;
                    }
                }
            }

            $moved = $reader->next();

            if ($values !== []) {
                yield $rowNumber => $values;
            }
        }

        $reader->close();
    }

    private function loadWorkbook(): void
    {
        $archive = new ZipArchive;

        if ($archive->open($this->path) !== true) {
            throw ValidationException::withMessages(['file' => 'Unable to open the Excel file.']);
        }

        $workbookXml = $archive->getFromName('xl/workbook.xml');
        $relationshipsXml = $archive->getFromName('xl/_rels/workbook.xml.rels');
        $hasSharedStrings = $archive->locateName('xl/sharedStrings.xml') !== false;
        $archive->close();

        if ($workbookXml === false || $relationshipsXml === false) {
            throw ValidationException::withMessages(['file' => 'The file is not a valid Excel .xlsx workbook.']);
        }

        $targets = [];
        $relationships = simplexml_load_string($relationshipsXml);

        foreach ($relationships->Relationship ?? [] as $relationship) {
            $target = ltrim((string) $relationship['Target'], '/');
            $targets[(string) $relationship['Id']] = str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
        }

        $workbook = simplexml_load_string($workbookXml);

        foreach ($workbook->sheets->sheet ?? [] as $sheet) {
            $relationshipId = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];

            if (isset($targets[$relationshipId])) {
                $this->sheetPaths[(string) $sheet['name']] = $targets[$relationshipId];
            }
        }

        if ($hasSharedStrings) {
            $this->loadSharedStrings();
        }
    }

    private function loadSharedStrings(): void
    {
        $reader = new XMLReader;
        $reader->open($this->zipUri('xl/sharedStrings.xml'));

        $moved = $reader->read();

        while ($moved) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'si') {
                $moved = $reader->read();

                continue;
            }

            $node = $reader->expand();
            $text = '';

            if ($node !== false) {
                foreach ($node->getElementsByTagNameNS(self::MAIN_NAMESPACE, 't') as $textNode) {
                    if ($textNode->parentNode?->localName !== 'rPh') {
                        $text .= $textNode->textContent;
                    }
                }
            }

            $this->sharedStrings[] = $text;
            $moved = $reader->next();
        }

        $reader->close();
    }

    private function cellValue(\DOMElement $cell): string
    {
        $type = $cell->getAttribute('t');

        if ($type === 'inlineStr') {
            return trim($cell->textContent);
        }

        $value = '';

        foreach ($cell->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'v') {
                $value = $child->textContent;

                break;
            }
        }

        if ($type === 's') {
            return trim($this->sharedStrings[(int) $value] ?? '');
        }

        if ($type === 'e') {
            return '';
        }

        return trim($value);
    }

    private function columnIndex(string $reference): int
    {
        $index = 0;
        $length = strlen($reference);

        for ($position = 0; $position < $length; $position++) {
            $character = $reference[$position];

            if ($character < 'A' || $character > 'Z') {
                break;
            }

            $index = ($index * 26) + (ord($character) - 64);
        }

        return $index - 1;
    }

    private function zipUri(string $entry): string
    {
        return 'zip://'.str_replace('\\', '/', $this->path).'#'.$entry;
    }
}
