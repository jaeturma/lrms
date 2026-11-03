<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\District;
use App\Models\Municipality;
use Database\Seeders\Concerns\ReadsSeedData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DivisionSchoolSeeder extends Seeder
{
    use ReadsSeedData;

    /**
     * Profile columns copied from the snapshot as-is.
     */
    private const PROFILE_COLUMNS = [
        'school_id', 'school_name', 'school_type', 'school_head', 'librarian', 'lr_designate',
        'ict_coordinator', 'math_coordinator', 'science_coordinator', 'property_custodian',
        'primary_mobile_no', 'secondary_mobile_no', 'email',
    ];

    /**
     * Seed the division's school list from the snapshot in
     * database/seeders/data. Run DivisionLocationSeeder first so locations
     * resolve. Schools already present (matched by school ID, including
     * soft-deleted ones) are never overwritten, so profile edits made in
     * production survive re-runs. Seeded schools start unlinked and not
     * activated.
     */
    public function run(): void
    {
        $municipalityIds = Municipality::query()->pluck('id', 'name');
        $districtIds = District::query()->get()->mapWithKeys(fn (District $district): array => ["{$district->municipality_id}|{$district->name}" => $district->id]);
        $barangayIds = Barangay::query()->get()->mapWithKeys(fn (Barangay $barangay): array => ["{$barangay->municipality_id}|{$barangay->name}" => $barangay->id]);
        $now = now();

        $rows = array_map(function (array $row) use ($municipalityIds, $districtIds, $barangayIds, $now): array {
            $municipalityId = $row['municipality'] !== null ? $municipalityIds[$row['municipality']] ?? null : null;

            return [
                ...array_intersect_key($row, array_flip(self::PROFILE_COLUMNS)),
                'municipality_id' => $municipalityId,
                'district_id' => $row['district'] !== null ? $districtIds["{$municipalityId}|{$row['district']}"] ?? null : null,
                'barangay_id' => $row['barangay'] !== null ? $barangayIds["{$municipalityId}|{$row['barangay']}"] ?? null : null,
                'is_activated' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $this->seedRows('schools.json'));

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('schools')->insertOrIgnore($chunk);
        }
    }
}
