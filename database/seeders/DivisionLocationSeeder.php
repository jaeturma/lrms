<?php

namespace Database\Seeders;

use App\Models\Municipality;
use Database\Seeders\Concerns\ReadsSeedData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DivisionLocationSeeder extends Seeder
{
    use ReadsSeedData;

    /**
     * Seed the division's full municipality, district and barangay list from
     * the snapshot in database/seeders/data. Existing rows are left as they
     * are, so re-runs never duplicate or overwrite.
     */
    public function run(): void
    {
        foreach ($this->seedRows('municipalities.json') as $row) {
            Municipality::query()->firstOrCreate(['name' => $row['name']]);
        }

        $municipalityIds = Municipality::query()->pluck('id', 'name');
        $now = now();

        foreach (['districts' => 'districts.json', 'barangays' => 'barangays.json'] as $table => $file) {
            $rows = array_map(fn (array $row): array => [
                'municipality_id' => $municipalityIds[$row['municipality']],
                'name' => $row['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ], $this->seedRows($file));

            DB::table($table)->insertOrIgnore($rows);
        }
    }
}
