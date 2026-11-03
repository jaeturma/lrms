<?php

use App\Models\Barangay;
use App\Models\District;
use App\Models\Municipality;
use App\Models\School;
use App\Models\ValidLrAllocation;
use App\Models\ValidLrLrmisCount;
use App\Models\ValidLrSchool;
use App\Models\ValidLrSmeItem;
use App\Models\ValidLrTitle;
use App\Models\ValidLrTvlItem;
use App\Models\ValidLrTvlProgram;
use Database\Seeders\DivisionLocationSeeder;
use Database\Seeders\DivisionSchoolSeeder;
use Database\Seeders\ValidLrReferenceSeeder;

/**
 * Row count of a snapshot file in database/seeders/data.
 */
function seedFileRowCount(string $file): int
{
    return count(json_decode(file_get_contents(database_path("seeders/data/{$file}")), true)['rows']);
}

test('the division location and school seeders load the snapshot idempotently', function () {
    $this->seed([DivisionLocationSeeder::class, DivisionSchoolSeeder::class]);
    $this->seed([DivisionLocationSeeder::class, DivisionSchoolSeeder::class]);

    expect(Municipality::count())->toBe(seedFileRowCount('municipalities.json'));
    expect(District::count())->toBe(seedFileRowCount('districts.json'));
    expect(Barangay::count())->toBe(seedFileRowCount('barangays.json'));
    expect(School::count())->toBe(seedFileRowCount('schools.json'));
    expect(School::where('is_activated', true)->orWhereNotNull('user_id')->exists())->toBeFalse();
});

test('the division school seeder never overwrites an existing school', function () {
    $this->seed([DivisionLocationSeeder::class, DivisionSchoolSeeder::class]);

    $school = School::query()->firstOrFail();
    $school->update(['school_head' => 'Edited In Production']);

    $this->seed(DivisionSchoolSeeder::class);

    expect($school->fresh()->school_head)->toBe('Edited In Production');
});

test('the valid-lr reference seeder loads the snapshot idempotently', function () {
    $this->seed(ValidLrReferenceSeeder::class);
    $this->seed(ValidLrReferenceSeeder::class);

    expect(ValidLrSchool::count())->toBe(seedFileRowCount('valid_lr_schools.json'));
    expect(ValidLrAllocation::count())->toBe(seedFileRowCount('valid_lr_allocations.json'));
    expect(ValidLrLrmisCount::count())->toBe(seedFileRowCount('valid_lr_lrmis_counts.json'));
    expect(ValidLrTvlProgram::count())->toBe(seedFileRowCount('valid_lr_tvl_programs.json'));
    expect(ValidLrTitle::count())->toBe(seedFileRowCount('valid_lr_titles.json'));
    expect(ValidLrSmeItem::count())->toBe(seedFileRowCount('valid_lr_sme_items.json'));
    expect(ValidLrTvlItem::count())->toBe(seedFileRowCount('valid_lr_tvl_items.json'));
});
