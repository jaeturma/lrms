<?php

use App\Models\District;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\IctEquipment;
use App\Models\LearningResource;
use App\Models\Municipality;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function createReportsSchool(array $attributes = []): School
{
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    return School::factory()->create(array_merge([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
    ], $attributes));
}

function seedReportsEnrollment(School $school, SchoolYear $schoolYear, int $male, int $female): void
{
    Enrollment::factory()->create([
        'school_id' => $school->id,
        'school_year_id' => $schoolYear->id,
        'grade_level_id' => GradeLevel::factory()->create()->id,
        'male_count' => $male,
        'female_count' => $female,
    ]);
}

function seedReportsInventory(School $school, int $available): void
{
    $resource = LearningResource::factory()->create(['school_id' => $school->id]);
    $resource->inventory()->create(['available' => $available]);
}

test('admin sees learning resource adequacy for the active school year', function () {
    $admin = User::factory()->admin()->create();
    $schoolYear = SchoolYear::factory()->active()->create();

    $school = createReportsSchool(['school_name' => 'Adequacy Test School']);

    seedReportsEnrollment($school, $schoolYear, 60, 40);
    seedReportsInventory($school, 30);

    $this->actingAs($admin)
        ->get(route('admin.reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminReports')
            ->has('resourceAdequacy', 1)
            ->where('resourceAdequacy.0.school_name', 'Adequacy Test School')
            ->where('resourceAdequacy.0.learners', 100)
            ->where('resourceAdequacy.0.available_copies', 30)
            ->where('resourceAdequacy.0.copies_per_learner', 0.3)
            ->where('resourceAdequacy.0.shortage', 70)
            ->where('resourceSummary.total_learners', 100)
            ->where('resourceSummary.total_available', 30)
            ->where('resourceSummary.schools_in_shortage', 1)
        );
});

test('resource adequacy respects the district filter', function () {
    $admin = User::factory()->admin()->create();
    SchoolYear::factory()->active()->create();

    $schoolInDistrict = createReportsSchool(['school_name' => 'Inside District School']);
    createReportsSchool(['school_name' => 'Outside District School']);

    $this->actingAs($admin)
        ->get(route('admin.reports.index', ['district_id' => $schoolInDistrict->district_id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminReports')
            ->has('resourceAdequacy', 1)
            ->where('resourceAdequacy.0.school_name', 'Inside District School')
        );
});

test('resource adequacy uses the selected school year', function () {
    $admin = User::factory()->admin()->create();
    $activeYear = SchoolYear::factory()->active()->create();
    $previousYear = SchoolYear::factory()->create();

    $school = createReportsSchool();

    seedReportsEnrollment($school, $activeYear, 30, 20);
    seedReportsEnrollment($school, $previousYear, 70, 30);

    $this->actingAs($admin)
        ->get(route('admin.reports.index', ['school_year_id' => $previousYear->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminReports')
            ->where('filters.school_year_id', $previousYear->id)
            ->where('resourceAdequacy.0.learners', 100)
        );
});

test('ict equipment summary groups counts by category and condition', function () {
    $admin = User::factory()->admin()->create();

    $school = createReportsSchool();

    IctEquipment::factory()->count(2)->create([
        'school_id' => $school->id,
        'category' => 'Laptop',
        'condition' => 'Good',
        'status' => 'Available',
    ]);
    IctEquipment::factory()->create([
        'school_id' => $school->id,
        'category' => 'Laptop',
        'condition' => 'Needs Repair',
        'status' => 'In Use',
    ]);
    IctEquipment::factory()->create([
        'school_id' => $school->id,
        'category' => 'Tablet',
        'condition' => 'Good',
        'status' => 'Available',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminReports')
            ->has('ictEquipmentByCategory', 2)
            ->where('ictEquipmentByCategory.0.category', 'Laptop')
            ->where('ictEquipmentByCategory.0.conditions.Good', 2)
            ->where('ictEquipmentByCategory.0.conditions.Needs Repair', 1)
            ->where('ictEquipmentByCategory.0.total', 3)
            ->where('ictEquipmentByCategory.1.category', 'Tablet')
            ->where('ictEquipmentByCategory.1.total', 1)
            ->where('ictEquipmentByStatus.Available', 3)
            ->where('ictEquipmentByStatus.In Use', 1)
        );
});

test('learning resource adequacy can be exported as csv', function () {
    $admin = User::factory()->admin()->create();
    $schoolYear = SchoolYear::factory()->active()->create();

    $school = createReportsSchool(['school_name' => 'Export Test School']);

    seedReportsEnrollment($school, $schoolYear, 25, 25);
    seedReportsInventory($school, 10);

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.learning-resources.export'))
        ->assertOk()
        ->assertDownload('learning-resource-adequacy.csv');

    $csv = $response->streamedContent();

    expect($csv)->toContain('"School ID","School Name",District,Municipality,Learners,"Available Copies","Copies per Learner",Shortage');
    expect($csv)->toContain('Export Test School');
    expect($csv)->toContain('50,10,0.2,40');
});

test('ict equipment summary can be exported as csv', function () {
    $admin = User::factory()->admin()->create();

    $school = createReportsSchool(['school_name' => 'Equipment Export School']);

    IctEquipment::factory()->count(2)->create([
        'school_id' => $school->id,
        'category' => 'Laptop',
        'condition' => 'Good',
        'status' => 'Available',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.ict-equipment.export'))
        ->assertOk()
        ->assertDownload('ict-equipment-summary.csv');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Equipment Export School');
    expect($csv)->toContain('Laptop,Good,Available,2');
});

test('school users cannot access reports or exports', function () {
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    $school = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'is_activated' => true,
    ]);

    $user = User::factory()->schoolUser($school)->create();

    $this->actingAs($user)->get(route('admin.reports.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.reports.learning-resources.export'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.reports.ict-equipment.export'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.reports.other-equipment.export'))->assertForbidden();
});
