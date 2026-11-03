<?php

namespace Database\Seeders;

use App\Models\ValidLrSchool;
use Database\Seeders\Concerns\ReadsSeedData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ValidLrReferenceSeeder extends Seeder
{
    use ReadsSeedData;

    private const CHUNK_SIZE = 500;

    /**
     * Seed the VALID-LR reference data (BEIS schools, allocations, LRMIS
     * counts, TVL programs, titles, SME and TVL masterlists) from the
     * snapshot in database/seeders/data. Keyed tables are upserted so re-runs
     * stay idempotent; the SME and TVL masterlists have no natural key and
     * are only seeded while empty, so a later workbook import is never
     * clobbered. Monitoring visits are transactional and are not seeded.
     */
    public function run(): void
    {
        $now = now();
        $timestamps = ['created_at' => $now, 'updated_at' => $now];

        $this->upsert('valid_lr_schools', array_map(
            fn (array $row): array => [...$row, ...$timestamps],
            $this->seedRows('valid_lr_schools.json'),
        ), ['beis_school_id']);

        $schoolIds = ValidLrSchool::query()->pluck('id', 'beis_school_id');
        $withSchoolId = fn (array $row): array => [
            'valid_lr_school_id' => $schoolIds[$row['beis_school_id']],
            ...array_diff_key($row, ['beis_school_id' => true]),
            ...$timestamps,
        ];

        $this->upsert('valid_lr_allocations', array_map($withSchoolId, $this->seedRows('valid_lr_allocations.json')), ['valid_lr_school_id', 'grade_level', 'learning_area']);
        $this->upsert('valid_lr_lrmis_counts', array_map($withSchoolId, $this->seedRows('valid_lr_lrmis_counts.json')), ['valid_lr_school_id', 'grade_level', 'learning_area']);
        $this->upsert('valid_lr_tvl_programs', array_map($withSchoolId, $this->seedRows('valid_lr_tvl_programs.json')), ['valid_lr_school_id', 'specialization']);

        $this->upsert('valid_lr_titles', array_map(
            fn (array $row): array => [...$row, ...$timestamps],
            $this->seedRows('valid_lr_titles.json'),
        ), ['region', 'grade_level', 'learning_area']);

        foreach (['valid_lr_sme_items', 'valid_lr_tvl_items'] as $table) {
            if (DB::table($table)->exists()) {
                $this->command?->warn("{$table} already has data; skipping.");

                continue;
            }

            foreach (array_chunk($this->seedRows("{$table}.json"), self::CHUNK_SIZE) as $chunk) {
                DB::table($table)->insert(array_map(fn (array $row): array => [...$row, ...$timestamps], $chunk));
            }
        }
    }

    /**
     * Upsert rows in chunks, refreshing every non-key column except created_at.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $uniqueBy
     */
    private function upsert(string $table, array $rows, array $uniqueBy): void
    {
        if ($rows === []) {
            return;
        }

        $update = array_values(array_diff(array_keys($rows[0]), [...$uniqueBy, 'created_at']));

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            DB::table($table)->upsert($chunk, $uniqueBy, $update);
        }
    }
}
