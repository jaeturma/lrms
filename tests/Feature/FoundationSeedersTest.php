<?php

use App\Models\Barangay;
use App\Models\District;
use App\Models\Municipality;
use App\Models\School;
use App\Models\SchoolYear;
use Database\Seeders\LocationSeeder;
use Database\Seeders\SchoolSeeder;
use Database\Seeders\SchoolYearSeeder;

test('the location seeder creates the division locations idempotently', function () {
    $this->seed(LocationSeeder::class);
    $this->seed(LocationSeeder::class);

    expect(Municipality::count())->toBe(11);
    expect(District::count())->toBe(19);
    expect(Barangay::count())->toBe(48);

    $nabunturan = Municipality::where('name', 'Nabunturan')->sole();

    expect(District::where('municipality_id', $nabunturan->id)->pluck('name')->all())
        ->toBe(['Nabunturan I', 'Nabunturan II']);
    expect(Barangay::where('municipality_id', $nabunturan->id)->count())->toBe(5);
});

test('the school seeder creates two linked schools per municipality idempotently', function () {
    $this->seed(LocationSeeder::class);
    $this->seed(SchoolSeeder::class);
    $this->seed(SchoolSeeder::class);

    expect(School::count())->toBe(22);

    $school = School::where('school_name', 'Compostela Central Elementary School')->sole();

    expect($school->school_id)->toStartWith('SID-');
    expect($school->municipality->name)->toBe('Compostela');
    expect($school->district->name)->toBe('Compostela I');
    expect($school->barangay->name)->toBe('Poblacion');
    expect($school->is_activated)->toBeFalse();
});

test('the school seeder warns and skips when locations are missing', function () {
    $this->seed(SchoolSeeder::class);

    expect(School::count())->toBe(0);
});

test('the location seeder never touches an already populated location list', function () {
    Municipality::factory()->create(['name' => 'Imported Municipality']);

    $this->seed(LocationSeeder::class);

    expect(Municipality::count())->toBe(1);
    expect(District::count())->toBe(0);
    expect(Barangay::count())->toBe(0);
});

test('the school seeder never adds sample schools to a system with real schools', function () {
    $existing = School::factory()->create();

    $this->seed(SchoolSeeder::class);

    expect(School::count())->toBe(1);
    expect(School::sole()->id)->toBe($existing->id);
});

test('the school year seeder creates years and guarantees one active year', function () {
    $this->seed(SchoolYearSeeder::class);

    expect(SchoolYear::count())->toBe(3);
    expect(SchoolYear::where('is_active', true)->sole()->name)->toBe('2026-2027');
});

test('the school year seeder does not steal the active flag from an existing year', function () {
    SchoolYear::factory()->active()->create(['name' => '2025-2026']);

    $this->seed(SchoolYearSeeder::class);

    expect(SchoolYear::where('is_active', true)->sole()->name)->toBe('2025-2026');
});
