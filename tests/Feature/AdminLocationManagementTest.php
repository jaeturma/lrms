<?php

use App\Models\Barangay;
use App\Models\District;
use App\Models\Municipality;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view location management page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.locations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminLocationsPage')
            ->where('activeModule', 'all')
            ->has('districts')
            ->has('municipalities')
            ->has('barangays')
        );
});

test('admin can open dedicated location modules', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.districts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminLocationsPage')
            ->where('activeModule', 'districts')
        );

    $this->actingAs($admin)
        ->get(route('admin.municipalities.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminLocationsPage')
            ->where('activeModule', 'municipalities')
        );

    $this->actingAs($admin)
        ->get(route('admin.barangays.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminLocationsPage')
            ->where('activeModule', 'barangays')
        );
});

test('admin can create district municipality and barangay manually', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.locations.store', ['type' => 'districts']), [
            'municipality_id' => Municipality::factory()->create()->id,
            'name' => 'District North',
        ])
        ->assertRedirect();

    $district = District::where('name', 'District North')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('admin.locations.store', ['type' => 'municipalities']), [
            'name' => 'San Roque',
        ])
        ->assertRedirect();

    $municipality = Municipality::where('name', 'San Roque')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('admin.locations.store', ['type' => 'barangays']), [
            'municipality_id' => $municipality->id,
            'name' => 'Poblacion',
        ])
        ->assertRedirect();

    expect(Barangay::where('name', 'Poblacion')->exists())->toBeTrue();
});

test('admin can update location names', function () {
    $admin = User::factory()->admin()->create();
    $municipality = Municipality::factory()->create(['name' => 'Town Old']);
    $district = District::factory()->create(['municipality_id' => $municipality->id, 'name' => 'District Old']);
    $barangay = Barangay::factory()->create(['municipality_id' => $municipality->id, 'name' => 'Barangay Old']);

    $this->actingAs($admin)
        ->put(route('admin.locations.update', ['type' => 'districts', 'id' => $district->id]), [
            'municipality_id' => $municipality->id,
            'name' => 'District New',
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->put(route('admin.locations.update', ['type' => 'municipalities', 'id' => $municipality->id]), [
            'name' => 'Town New',
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->put(route('admin.locations.update', ['type' => 'barangays', 'id' => $barangay->id]), [
            'municipality_id' => $municipality->id,
            'name' => 'Barangay New',
        ])
        ->assertRedirect();

    expect($district->fresh()->name)->toBe('District New');
    expect($municipality->fresh()->name)->toBe('Town New');
    expect($barangay->fresh()->name)->toBe('Barangay New');
});

test('admin can import locations from csv', function () {
    $admin = User::factory()->admin()->create();

    $csv = UploadedFile::fake()->createWithContent('locations.csv', implode("\n", [
        'municipality,district,barangay',
        'San Isidro,District I,Poblacion',
        'San Isidro,District I,North Baybay',
        'Santa Maria,District II,',
        'Maco,District III,',
        'San Isidro,District I,Poblacion',
    ]));

    $response = $this->actingAs($admin)
        ->post(route('admin.locations.import'), [
            'csv' => $csv,
        ]);

    $response->assertRedirect(route('admin.locations.index'));

    expect(District::where('name', 'District I')->exists())->toBeTrue();
    expect(Municipality::where('name', 'San Isidro')->exists())->toBeTrue();
    expect(Barangay::where('name', 'North Baybay')->exists())->toBeTrue();
});

test('admin can download location import template', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.locations.template'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $response->assertHeader('content-disposition', 'attachment; filename=location-template.csv');
});

test('admin cannot delete district that is already used by a school', function () {
    $admin = User::factory()->admin()->create();
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'barangay_id' => null,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.locations.destroy', ['type' => 'districts', 'id' => $district->id]))
        ->assertStatus(422);

    expect(District::whereKey($district->id)->exists())->toBeTrue();
});
