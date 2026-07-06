<?php

use App\Models\District;
use App\Models\LearningResource;
use App\Models\LearningResourceType;
use App\Models\Municipality;
use App\Models\School;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests do not receive school contact details on the activation page', function () {
    $school = School::factory()->create([
        'school_id' => 'SID-77001',
        'is_activated' => false,
        'school_head' => 'Maria Dela Cruz',
        'email' => 'head@example.com',
        'primary_mobile_no' => '09171234567',
    ]);

    $response = $this->get(route('school.activate.edit', $school));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolActivationPage')
            ->where('school.data.school_id', 'SID-77001')
            ->missing('school.data.email')
            ->missing('school.data.school_head')
            ->missing('school.data.primary_mobile_no')
        );
});

test('admins still receive school contact details', function () {
    $admin = User::factory()->admin()->create();

    $school = School::factory()->create([
        'school_id' => 'SID-77002',
        'school_head' => 'Maria Dela Cruz',
        'email' => 'head@example.com',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.schools.show', $school));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminSchoolShow')
            ->where('school.email', 'head@example.com')
            ->where('school.school_head', 'Maria Dela Cruz')
        );
});

test('otp verification endpoint is throttled', function () {
    $school = School::factory()->create([
        'school_id' => 'SID-77003',
        'is_activated' => false,
    ]);

    foreach (range(1, 6) as $attempt) {
        $this->post(route('school.activate.verify-otp', $school), ['otp' => '000000']);
    }

    $response = $this->post(route('school.activate.verify-otp', $school), ['otp' => '000000']);

    $response->assertStatus(429);
});

test('learning material type in use cannot be deleted', function () {
    $admin = User::factory()->admin()->create();
    $type = LearningResourceType::factory()->create(['name' => 'Textbook']);

    LearningResource::factory()->create([
        'learning_resource_type_id' => $type->id,
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.learning-resource-types.index'))
        ->delete(route('admin.learning-resource-types.destroy', $type));

    $response->assertSessionHasErrors('name');
    expect(LearningResourceType::whereKey($type->id)->exists())->toBeTrue();
});

test('deleting a school soft deletes it and keeps its learning resources', function () {
    $admin = User::factory()->admin()->create();
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    $school = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'school_id' => 'SID-77004',
    ]);

    $resource = LearningResource::factory()->create(['school_id' => $school->id]);

    $response = $this
        ->actingAs($admin)
        ->delete(route('admin.schools.destroy', $school));

    $response->assertRedirect(route('admin.dashboard'));

    $this->assertSoftDeleted('schools', ['id' => $school->id]);
    expect(LearningResource::whereKey($resource->id)->exists())->toBeTrue();
    expect(School::count())->toBe(0);
});
