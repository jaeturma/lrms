<?php

use App\Models\District;
use App\Models\LearningResourceType;
use App\Models\Municipality;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view dashboard', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.dashboard'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminDashboard')
            ->has('stats')
            ->has('districts')
            ->has('filters')
            ->has('reportsByDistrict')
            ->has('schools.data')
            ->has('learningResourceTypes')
        );
});

test('admin can import school csv and receives summary', function () {
    $admin = User::factory()->admin()->create();

    $csv = UploadedFile::fake()->createWithContent('schools.csv', implode("\n", [
        'district,municipality,barangay,school_id,school_name',
        'District A,Town A,Barangay A,SID-10001,Alpha School',
        'District A,Town A,Barangay B,SID-10002,Beta School',
        'District A,Town A,Barangay B,SID-10002,Beta School Duplicate',
    ]));

    $response = $this
        ->actingAs($admin)
        ->postJson(route('admin.import.store'), [
            'csv' => $csv,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('summary.imported', 2)
        ->assertJsonPath('summary.skipped', 1);

    expect(School::count())->toBe(2);
});

test('admin can manage learning material types', function () {
    $admin = User::factory()->admin()->create();

    $createResponse = $this
        ->actingAs($admin)
        ->post(route('admin.learning-resource-types.store'), [
            'name' => 'Digital Module',
        ]);

    $createResponse->assertRedirect();
    expect(LearningResourceType::where('name', 'Digital Module')->exists())->toBeTrue();

    $type = LearningResourceType::where('name', 'Digital Module')->firstOrFail();

    $updateResponse = $this
        ->actingAs($admin)
        ->put(route('admin.learning-resource-types.update', $type), [
            'name' => 'Digital Module',
            'is_active' => false,
        ]);

    $updateResponse->assertRedirect();
    expect($type->fresh()->is_active)->toBeFalse();

    $deleteResponse = $this
        ->actingAs($admin)
        ->delete(route('admin.learning-resource-types.destroy', $type));

    $deleteResponse->assertRedirect();
    expect(LearningResourceType::whereKey($type->id)->exists())->toBeFalse();
});

test('admin can update school details', function () {
    $admin = User::factory()->admin()->create();
    $district = District::factory()->create();
    $municipality = Municipality::factory()->create(['district_id' => $district->id]);

    $school = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'school_id' => 'SID-20260',
    ]);

    $response = $this
        ->actingAs($admin)
        ->put(route('admin.schools.update', $school), [
            'school_id' => 'SID-20260',
            'school_name' => 'Updated School Name',
            'district_id' => $district->id,
            'municipality_id' => $municipality->id,
            'barangay_id' => null,
            'school_head' => 'Updated Head',
            'librarian' => 'Updated Librarian',
            'property_custodian' => 'Updated Custodian',
            'email' => null,
        ]);

    $response->assertRedirect(route('admin.schools.edit', $school));

    expect($school->fresh()->school_name)->toBe('Updated School Name');
    expect($school->fresh()->school_head)->toBe('Updated Head');
});

test('admin can create school from management form', function () {
    $admin = User::factory()->admin()->create();
    $district = District::factory()->create();
    $municipality = Municipality::factory()->create(['district_id' => $district->id]);

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.schools.store'), [
            'school_id' => 'SID-30500',
            'school_name' => 'Newly Added School',
            'district_id' => $district->id,
            'municipality_id' => $municipality->id,
            'barangay_id' => null,
            'school_head' => null,
            'librarian' => null,
            'property_custodian' => null,
            'email' => null,
        ]);

    $school = School::where('school_id', 'SID-30500')->first();

    expect($school)->not->toBeNull();
    $response->assertRedirect(route('admin.schools.edit', $school));
});

test('admin can delete school and linked school user', function () {
    $admin = User::factory()->admin()->create();
    $school = School::factory()->create(['is_activated' => true]);
    $schoolUser = User::factory()->schoolUser($school)->create();

    $school->update([
        'user_id' => $schoolUser->id,
        'email' => $schoolUser->email,
    ]);

    $response = $this
        ->actingAs($admin)
        ->delete(route('admin.schools.destroy', $school));

    $response->assertRedirect(route('admin.dashboard'));

    expect(School::whereKey($school->id)->exists())->toBeFalse();
    expect(User::whereKey($schoolUser->id)->exists())->toBeFalse();
});
