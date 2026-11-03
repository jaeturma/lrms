<?php

namespace Database\Seeders\Concerns;

use RuntimeException;

trait ReadsSeedData
{
    /**
     * Read a snapshot file from database/seeders/data as a list of
     * associative rows keyed by the file's column names.
     *
     * @return list<array<string, mixed>>
     */
    protected function seedRows(string $file): array
    {
        $path = database_path("seeders/data/{$file}");

        if (! is_file($path)) {
            throw new RuntimeException("Seed data file not found: {$path}");
        }

        /** @var array{columns: list<string>, rows: list<list<mixed>>} $data */
        $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        return array_map(
            fn (array $row): array => array_combine($data['columns'], $row),
            $data['rows'],
        );
    }
}
