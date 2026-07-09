<?php

use App\Models\District;
use App\Models\Municipality;
use App\Models\School;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('school share prop reports profile complete when school head and email are filled', function () {
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    $school = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'is_activated' => true,
        'school_head' => 'Complete Head',
        'email' => 'complete-school@example.com',
    ]);

    $user = User::factory()->schoolUser($school)->create();

    $this->actingAs($user)
        ->get(route('school.ict-equipment.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('school.school_id', $school->school_id)
            ->where('school.is_profile_complete', true)
        );
});

test('school share prop reports profile incomplete when school head or email is missing', function () {
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    $school = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'is_activated' => true,
        'school_head' => null,
        'email' => 'incomplete-school@example.com',
    ]);

    $user = User::factory()->schoolUser($school)->create();

    $this->actingAs($user)
        ->get(route('school.ict-equipment.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('school.is_profile_complete', false)
        );
});

test('school share prop is absent for admin workspace users', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('school', null));
});
