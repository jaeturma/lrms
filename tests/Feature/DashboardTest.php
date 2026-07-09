<?php

use App\Models\Barangay;
use App\Models\District;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\IctEquipment;
use App\Models\LearningResource;
use App\Models\Municipality;
use App\Models\ResourceDistribution;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $school = School::factory()->create([
        'is_activated' => true,
        'school_head' => 'Maria Head',
    ]);
    $user = User::factory()->schoolUser($school)->create();
    $school->update(['user_id' => $user->id, 'email' => $user->email]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolDashboard')
            ->has('school')
            ->where('school.data.school_id', $school->school_id)
            ->where('school.data.school_name', $school->school_name)
            ->has('stats')
            ->has('enrollmentByGrade')
            ->has('equipmentCondition')
        );
});

test('school dashboard aggregates reflect the schools own data', function () {
    $school = School::factory()->create([
        'is_activated' => true,
        'school_head' => 'Aggregate Head',
    ]);
    $user = User::factory()->schoolUser($school)->create();
    $school->update(['user_id' => $user->id, 'email' => $user->email]);

    $otherSchool = School::factory()->create(['is_activated' => true]);

    $schoolYear = SchoolYear::factory()->active()->create();
    $grade = GradeLevel::factory()->create(['name' => 'Grade 4', 'sort_order' => 4]);

    Enrollment::factory()->create([
        'school_id' => $school->id,
        'school_year_id' => $schoolYear->id,
        'grade_level_id' => $grade->id,
        'male_count' => 25,
        'female_count' => 15,
    ]);
    Enrollment::factory()->create([
        'school_id' => $otherSchool->id,
        'school_year_id' => $schoolYear->id,
        'grade_level_id' => $grade->id,
        'male_count' => 99,
        'female_count' => 99,
    ]);

    LearningResource::factory()->create([
        'school_id' => $school->id,
        'quantity_delivered' => 40,
        'quantity_with_issue_defect' => 4,
    ]);

    IctEquipment::factory()->create(['school_id' => $school->id, 'condition' => 'Good']);
    IctEquipment::factory()->create(['school_id' => $school->id, 'condition' => 'Beyond Repair']);
    IctEquipment::factory()->create(['school_id' => $otherSchool->id, 'condition' => 'Good']);

    ResourceDistribution::factory()->create(['school_id' => $school->id, 'status' => 'pending']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolDashboard')
            ->where('stats.total_learners', 40)
            ->where('stats.male_learners', 25)
            ->where('stats.female_learners', 15)
            ->where('stats.copies_delivered', 40)
            ->where('stats.copies_with_defects', 4)
            ->where('stats.defect_rate', 10)
            ->where('stats.total_equipment', 2)
            ->where('stats.equipment_needing_repair', 1)
            ->where('stats.pending_distributions', 1)
            ->where('enrollmentByGrade.0.grade', 'Grade 4')
            ->where('enrollmentByGrade.0.male', 25)
            ->where('enrollmentByGrade.0.female', 15)
            ->where('equipmentCondition.0.type', 'ICT Equipment')
            ->where('equipmentCondition.0.good', 1)
            ->where('equipmentCondition.0.needs_attention', 1)
        );
});

test('authenticated school users can open the learning resources page', function () {
    $school = School::factory()->create([
        'is_activated' => true,
        'school_head' => 'Maria Head',
        'email' => 'school-learning@example.com',
    ]);
    $user = User::factory()->schoolUser($school)->create([
        'email' => 'school-learning@example.com',
    ]);
    $school->update(['user_id' => $user->id]);

    $response = $this
        ->actingAs($user)
        ->get(route('school.resources.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolLearningResources')
            ->has('school')
            ->has('learningResources')
            ->has('learningResourceTypes')
        );
});

test('school user without updated details is redirected to school update page', function () {
    $school = School::factory()->create([
        'is_activated' => true,
        'school_head' => null,
    ]);
    $user = User::factory()->schoolUser($school)->create();
    $school->update(['user_id' => $user->id, 'email' => $user->email]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertRedirect(route('school.activate.edit', $school));
});

test('dashboard immediately reflects updated school fields after activation update', function () {
    $school = School::factory()->create([
        'is_activated' => true,
        'school_head' => 'Old Head',
        'librarian' => 'Old Librarian',
        'property_custodian' => 'Old Custodian',
        'primary_mobile_no' => '09111111111',
        'secondary_mobile_no' => '09222222222',
        'email' => 'old-school@example.com',
    ]);

    $user = User::factory()->schoolUser($school)->create([
        'email' => 'old-school@example.com',
    ]);

    $school->update(['user_id' => $user->id]);

    $payload = [
        'school_head' => 'NEW SCHOOL HEAD',
        'librarian' => 'NEW LIBRARIAN',
        'property_custodian' => 'NEW CUSTODIAN',
        'primary_mobile_no' => '09999999999',
        'secondary_mobile_no' => '09888888888',
        'email' => 'updated-school@example.com',
        'municipality_id' => $school->municipality_id,
        'district_id' => $school->district_id,
        'barangay_id' => $school->barangay_id,
    ];

    $this
        ->actingAs($user)
        ->post(route('school.activate.store', $school), $payload)
        ->assertRedirect(route('dashboard'));

    $school->refresh();
    $user->refresh();

    expect($school->school_head)->toBe('NEW SCHOOL HEAD');
    expect($school->librarian)->toBe('NEW LIBRARIAN');
    expect($school->property_custodian)->toBe('NEW CUSTODIAN');
    expect($school->primary_mobile_no)->toBe('09999999999');
    expect($school->secondary_mobile_no)->toBe('09888888888');
    expect($school->email)->toBe('updated-school@example.com');
    expect($user->email)->toBe('updated-school@example.com');

    $this
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolDashboard')
            ->where('school.data.school_head', 'NEW SCHOOL HEAD')
            ->where('school.data.librarian', 'NEW LIBRARIAN')
            ->where('school.data.property_custodian', 'NEW CUSTODIAN')
            ->where('school.data.primary_mobile_no', '09999999999')
            ->where('school.data.secondary_mobile_no', '09888888888')
            ->where('school.data.email', 'updated-school@example.com')
        );
});

test('dashboard immediately reflects updated school location names after activation update', function () {
    $oldMunicipality = Municipality::factory()->create(['name' => 'OLD MUNICIPALITY']);
    $oldDistrict = District::factory()->create([
        'municipality_id' => $oldMunicipality->id,
        'name' => 'OLD DISTRICT',
    ]);
    $oldBarangay = Barangay::factory()->create([
        'municipality_id' => $oldMunicipality->id,
        'name' => 'OLD BARANGAY',
    ]);

    $newMunicipality = Municipality::factory()->create(['name' => 'NEW MUNICIPALITY']);
    $newDistrict = District::factory()->create([
        'municipality_id' => $newMunicipality->id,
        'name' => 'NEW DISTRICT',
    ]);
    $newBarangay = Barangay::factory()->create([
        'municipality_id' => $newMunicipality->id,
        'name' => 'NEW BARANGAY',
    ]);

    $school = School::factory()->create([
        'is_activated' => true,
        'school_head' => 'INITIAL HEAD',
        'email' => 'location-old@example.com',
        'municipality_id' => $oldMunicipality->id,
        'district_id' => $oldDistrict->id,
        'barangay_id' => $oldBarangay->id,
    ]);

    $user = User::factory()->schoolUser($school)->create([
        'email' => 'location-old@example.com',
    ]);

    $school->update(['user_id' => $user->id]);

    $payload = [
        'school_head' => 'INITIAL HEAD',
        'librarian' => $school->librarian,
        'property_custodian' => $school->property_custodian,
        'primary_mobile_no' => $school->primary_mobile_no,
        'secondary_mobile_no' => $school->secondary_mobile_no,
        'email' => 'location-updated@example.com',
        'municipality_id' => $newMunicipality->id,
        'district_id' => $newDistrict->id,
        'barangay_id' => $newBarangay->id,
    ];

    $this
        ->actingAs($user)
        ->post(route('school.activate.store', $school), $payload)
        ->assertRedirect(route('dashboard'));

    $school->refresh();
    $user->refresh();

    expect($school->municipality_id)->toBe($newMunicipality->id);
    expect($school->district_id)->toBe($newDistrict->id);
    expect($school->barangay_id)->toBe($newBarangay->id);
    expect($school->email)->toBe('location-updated@example.com');
    expect($user->email)->toBe('location-updated@example.com');

    $this
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolDashboard')
            ->where('school.data.municipality', 'NEW MUNICIPALITY')
            ->where('school.data.district', 'NEW DISTRICT')
            ->where('school.data.barangay', 'NEW BARANGAY')
            ->where('school.data.municipality_id', $newMunicipality->id)
            ->where('school.data.district_id', $newDistrict->id)
            ->where('school.data.barangay_id', $newBarangay->id)
        );
});
