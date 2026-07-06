<?php

use App\Models\District;
use App\Models\LearningResource;
use App\Models\LearningResourceType;
use App\Models\Municipality;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated admin visiting admin login is redirected to admin dashboard', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.login'));

    $response->assertRedirect(route('admin.dashboard'));
});

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
            ->missing('learningResourceTypes')
        );
});

test('admin can view learning material types page', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.learning-resource-types.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminLearningResourceTypes')
            ->has('learningResourceTypes')
        );
});

test('admin can view learning materials page', function () {
    $admin = User::factory()->admin()->create();
    $school = School::factory()->create([
        'school_id' => 'SID-90123',
        'school_name' => 'North District School',
    ]);

    $type = LearningResourceType::factory()->create(['name' => 'Textbook']);

    LearningResource::factory()->create([
        'school_id' => $school->id,
        'learning_resource_type_id' => $type->id,
        'title' => 'Science 6',
        'publisher' => 'DepEd',
        'quantity_delivered' => 50,
        'quantity_with_issue_defect' => 2,
    ]);

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.learning-materials.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminLearningMaterials')
            ->has('filters')
            ->has('materials.data', 1)
            ->where('materials.data.0.school_id', 'SID-90123')
            ->where('materials.data.0.resource_type', 'Textbook')
        );
});

test('admin can import school csv and receives summary', function () {
    $admin = User::factory()->admin()->create();

    $csv = UploadedFile::fake()->createWithContent('schools.csv', implode("\n", [
        'municipality,district,barangay,school_id,school_name',
        'Town A,District A,Barangay A,SID-10001,Alpha School',
        'Town A,District A,Barangay B,SID-10002,Beta School',
        'Town A,District A,Barangay B,SID-10002,Beta School Duplicate',
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

test('admin can import school csv with optional location fields', function () {
    $admin = User::factory()->admin()->create();

    $csv = UploadedFile::fake()->createWithContent('schools-optional-location.csv', implode("\n", [
        'municipality,district,barangay,school_id,school_name',
        ',,,SID-90901,No Location School',
        'Maco,,,,',
    ]));

    $response = $this
        ->actingAs($admin)
        ->postJson(route('admin.import.store'), [
            'csv' => $csv,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('summary.imported', 1)
        ->assertJsonPath('summary.skipped', 1);

    $school = School::query()->where('school_id', 'SID-90901')->firstOrFail();

    expect($school->municipality_id)->toBeNull();
    expect($school->district_id)->toBeNull();
    expect($school->barangay_id)->toBeNull();
});

test('admin can download school import template', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->get(route('admin.import.template'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $response->assertHeader('content-disposition', 'attachment; filename=school-import-template.csv');
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
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

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
            'school_type' => 'Integrated School',
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

test('admin can open school edit page with prefilled details', function () {
    $admin = User::factory()->admin()->create();
    $school = School::factory()->create([
        'school_id' => 'SID-50700',
        'school_name' => 'Prefill School',
        'school_type' => 'Elementary',
    ]);

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.schools.edit', $school));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminSchoolEdit')
            ->where('school.school_id', 'SID-50700')
            ->where('school.school_name', 'Prefill School')
            ->where('school.school_type', 'Elementary')
        );
});

test('admin can view school details and learning resources list', function () {
    $admin = User::factory()->admin()->create();
    $school = School::factory()->create([
        'school_id' => 'SID-60800',
        'school_name' => 'Viewable School',
    ]);

    $type = LearningResourceType::factory()->create(['name' => 'Textbook']);

    LearningResource::factory()->create([
        'school_id' => $school->id,
        'learning_resource_type_id' => $type->id,
        'title' => 'English Grade 7',
        'publisher' => 'Sample Publisher',
        'quantity_delivered' => 8,
        'quantity_with_issue_defect' => 1,
        'remarks' => 'Worn out cover',
    ]);

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.schools.show', $school));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminSchoolShow')
            ->where('school.school_id', 'SID-60800')
            ->where('school.school_name', 'Viewable School')
            ->has('learningResources', 1)
            ->where('learningResources.0.resource_type', 'Textbook')
        );
});

test('admin can create school from management form', function () {
    $admin = User::factory()->admin()->create();
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.schools.store'), [
            'school_id' => 'SID-30500',
            'school_name' => 'Newly Added School',
            'school_type' => 'Elementary',
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
