<?php

use App\Models\District;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\IctEquipment;
use App\Models\IctEquipmentCatalogItem;
use App\Models\LearningResource;
use App\Models\LearningResourceType;
use App\Models\Municipality;
use App\Models\ResourceDistribution;
use App\Models\ResourceTitle;
use App\Models\School;
use App\Models\SchoolYear;
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

test('admin login rejects oversized credentials', function () {
    $response = $this->post(route('admin.login.store'), [
        'email' => str_repeat('a', 43).'@example.com',
        'password' => str_repeat('p', 31),
    ]);

    $response->assertSessionHasErrors(['email', 'password']);

    $this->assertGuest();
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
            ->has('enrollmentByGrade')
            ->has('activationByMunicipality')
            ->has('equipmentCondition')
            ->has('defectRateByMunicipality')
            ->has('pendingActivations')
            ->has('schools.data')
            ->missing('learningResourceTypes')
        );
});

test('dashboard aggregates reflect collected data', function () {
    $admin = User::factory()->admin()->create();

    $municipality = Municipality::factory()->create(['name' => 'Maco']);
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    $activatedSchool = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'is_activated' => true,
    ]);
    $pendingSchool = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'is_activated' => false,
        'activation_requested_at' => now(),
    ]);

    $schoolYear = SchoolYear::factory()->active()->create();
    $grade = GradeLevel::factory()->create(['name' => 'Grade 1', 'sort_order' => 1]);

    Enrollment::factory()->create([
        'school_id' => $activatedSchool->id,
        'school_year_id' => $schoolYear->id,
        'grade_level_id' => $grade->id,
        'male_count' => 60,
        'female_count' => 40,
    ]);

    LearningResource::factory()->create([
        'school_id' => $activatedSchool->id,
        'quantity_delivered' => 50,
        'quantity_with_issue_defect' => 5,
    ]);

    IctEquipment::factory()->create(['school_id' => $activatedSchool->id, 'condition' => 'Good']);
    IctEquipment::factory()->create(['school_id' => $activatedSchool->id, 'condition' => 'Needs Repair']);

    ResourceDistribution::factory()->create(['school_id' => $activatedSchool->id, 'status' => 'pending']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminDashboard')
            ->where('stats.total_schools', 2)
            ->where('stats.activated_schools', 1)
            ->where('stats.pending_requests', 1)
            ->where('stats.total_learners', 100)
            ->where('stats.male_learners', 60)
            ->where('stats.female_learners', 40)
            ->where('stats.copies_delivered', 50)
            ->where('stats.copies_with_defects', 5)
            ->where('stats.defect_rate', 10)
            ->where('stats.total_equipment', 2)
            ->where('stats.equipment_needing_repair', 1)
            ->where('stats.pending_distributions', 1)
            ->where('enrollmentByGrade.0.grade', 'Grade 1')
            ->where('enrollmentByGrade.0.male', 60)
            ->where('enrollmentByGrade.0.female', 40)
            ->where('activationByMunicipality.0.municipality', 'Maco')
            ->where('activationByMunicipality.0.activated', 1)
            ->where('activationByMunicipality.0.total', 2)
            ->where('equipmentCondition.0.type', 'ICT Equipment')
            ->where('equipmentCondition.0.good', 1)
            ->where('equipmentCondition.0.needs_attention', 1)
            ->where('defectRateByMunicipality.0.municipality', 'Maco')
            ->where('defectRateByMunicipality.0.rate', 10)
            ->has('pendingActivations', 1)
            ->where('pendingActivations.0.school_id', $pendingSchool->school_id)
        );
});

test('dashboard scope filters narrow aggregates to the selected district, school type, and grade level', function () {
    $admin = User::factory()->admin()->create();

    $municipalityA = Municipality::factory()->create(['name' => 'Maco']);
    $districtA = District::factory()->create(['municipality_id' => $municipalityA->id]);
    $municipalityB = Municipality::factory()->create(['name' => 'Nabunturan']);
    $districtB = District::factory()->create(['municipality_id' => $municipalityB->id]);

    $schoolA = School::factory()->create([
        'district_id' => $districtA->id,
        'municipality_id' => $municipalityA->id,
        'school_type' => 'Elementary',
        'is_activated' => true,
    ]);
    $schoolB = School::factory()->create([
        'district_id' => $districtB->id,
        'municipality_id' => $municipalityB->id,
        'school_type' => 'Junior High School',
        'is_activated' => true,
    ]);

    $schoolYear = SchoolYear::factory()->active()->create();
    $gradeOne = GradeLevel::factory()->create(['name' => 'Grade 1', 'sort_order' => 1]);
    $gradeSeven = GradeLevel::factory()->create(['name' => 'Grade 7', 'sort_order' => 7]);

    Enrollment::factory()->create([
        'school_id' => $schoolA->id,
        'school_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeOne->id,
        'male_count' => 10,
        'female_count' => 5,
    ]);
    Enrollment::factory()->create([
        'school_id' => $schoolB->id,
        'school_year_id' => $schoolYear->id,
        'grade_level_id' => $gradeSeven->id,
        'male_count' => 20,
        'female_count' => 8,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['district_id' => $districtA->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminDashboard')
            ->where('stats.total_schools', 1)
            ->where('stats.total_learners', 15)
            ->where('filters.district_id', $districtA->id)
            ->has('schoolTypes', 5)
            ->has('gradeLevels', 2)
        );

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['school_type' => 'Junior High School']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminDashboard')
            ->where('stats.total_schools', 1)
            ->where('stats.total_learners', 28)
            ->where('filters.school_type', 'Junior High School')
        );

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['grade_level_id' => $gradeOne->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminDashboard')
            ->where('stats.total_learners', 15)
            ->has('enrollmentByGrade', 1)
            ->where('enrollmentByGrade.0.grade', 'Grade 1')
            ->where('filters.grade_level_id', $gradeOne->id)
        );

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminDashboard')
            ->where('stats.total_schools', 2)
            ->where('stats.total_learners', 43)
            ->where('filters.district_id', null)
            ->where('filters.school_type', null)
            ->where('filters.grade_level_id', null)
        );
});

test('executive roles can view the dashboard', function () {
    $asds = User::factory()->create(['role' => 'asds']);

    $this->actingAs($asds)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('AdminDashboard'));
});

test('system roles can access reference data and managers cannot', function () {
    $ito = User::factory()->create(['role' => 'ito']);
    $manager = User::factory()->create(['role' => 'manager']);

    $this
        ->actingAs($ito)
        ->get(route('admin.districts.index'))
        ->assertOk();

    $this
        ->actingAs($manager)
        ->get(route('admin.districts.index'))
        ->assertForbidden();
});

test('catalog roles can access catalogs and executive roles cannot', function () {
    $supply = User::factory()->create(['role' => 'supply']);
    $cidChief = User::factory()->create(['role' => 'cidchief']);

    $this
        ->actingAs($supply)
        ->get(route('admin.resource-titles.index'))
        ->assertOk();

    $this
        ->actingAs($supply)
        ->get(route('admin.ict-equipment-catalog.index'))
        ->assertOk();

    $this
        ->actingAs($cidChief)
        ->get(route('admin.resource-titles.index'))
        ->assertForbidden();

    $this
        ->actingAs($cidChief)
        ->get(route('admin.ict-equipment-catalog.index'))
        ->assertForbidden();
});

test('executive roles can only access monitoring modules', function () {
    $asds = User::factory()->create(['role' => 'asds']);

    $this->actingAs($asds)->get(route('admin.learning-materials.index'))->assertOk();
    $this->actingAs($asds)->get(route('admin.ict-equipment.index'))->assertOk();
    $this->actingAs($asds)->get(route('admin.schools.index'))->assertOk();
    $this->actingAs($asds)->get(route('admin.reports.index'))->assertOk();
    $this->actingAs($asds)->get(route('admin.settings.edit'))->assertForbidden();
    $this->actingAs($asds)->get(route('admin.distributions.index'))->assertForbidden();
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

test('manager can import learning resource catalog titles from a csv file', function () {
    $manager = User::factory()->create(['role' => 'manager']);
    LearningResourceType::factory()->create(['name' => 'Book']);

    $csv = UploadedFile::fake()->createWithContent('learning-resource-catalog.csv', implode("\n", [
        'resource_type,title,author,publisher,description,is_active',
        'Book,Mathematics 7,DepEd,Department of Education,Catalog title,1',
    ]));

    $response = $this
        ->actingAs($manager)
        ->postJson(route('admin.resource-titles.import.store'), [
            'file' => $csv,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('summary.imported', 1)
        ->assertJsonPath('summary.updated', 0)
        ->assertJsonPath('summary.skipped', 0);

    $title = ResourceTitle::query()->sole();

    expect($title->title)->toBe('Mathematics 7');
    expect($title->publisher)->toBe('Department of Education');
    expect($title->description)->toBe('Catalog title');
});

test('supply user can import equipment catalog entries from a csv file', function () {
    $supply = User::factory()->create(['role' => 'supply']);

    $csv = UploadedFile::fake()->createWithContent('equipment-catalog.csv', implode("\n", [
        'item_name,category,brand,model,specifications,manufacturer,description,is_active',
        'Laptop Computer,Laptop,Lenovo,ThinkPad,Portable computer,Lenovo,General-purpose laptop,1',
    ]));

    $response = $this
        ->actingAs($supply)
        ->postJson(route('admin.ict-equipment-catalog.import.store'), [
            'file' => $csv,
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('summary.imported', 1)
        ->assertJsonPath('summary.updated', 0)
        ->assertJsonPath('summary.skipped', 0);

    $equipment = IctEquipmentCatalogItem::query()->sole();

    expect($equipment->item_name)->toBe('Laptop Computer');
    expect($equipment->brand)->toBe('Lenovo');
    expect($equipment->model)->toBe('ThinkPad');
});

test('school users cannot import catalog learning resources or equipment', function () {
    $schoolUser = User::factory()->schoolUser()->create();
    $file = UploadedFile::fake()->createWithContent('import.csv', 'school_id,title');

    $this->actingAs($schoolUser)
        ->postJson(route('admin.resource-titles.import.store'), ['file' => $file])
        ->assertForbidden();

    $this->actingAs($schoolUser)
        ->postJson(route('admin.ict-equipment-catalog.import.store'), ['file' => $file])
        ->assertForbidden();
});

test('authorized staff can download import templates', function () {
    $librarian = User::factory()->create(['role' => 'librarian']);

    $this->actingAs($librarian)
        ->get(route('admin.resource-titles.import.template'))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=learning-resource-catalog-import-template.csv');

    $this->actingAs($librarian)
        ->get(route('admin.ict-equipment-catalog.import.template'))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=ict-equipment-catalog-import-template.csv');
});

test('admin can manage learning material types', function () {
    $admin = User::factory()->admin()->create();

    $createResponse = $this
        ->actingAs($admin)
        ->post(route('admin.learning-resource-types.store'), [
            'name' => 'Digital Module',
            'category' => 'Non-Print',
        ]);

    $createResponse->assertRedirect();
    expect(LearningResourceType::where('name', 'Digital Module')->exists())->toBeTrue();

    $type = LearningResourceType::where('name', 'Digital Module')->firstOrFail();

    expect($type->category)->toBe('Non-Print');

    $updateResponse = $this
        ->actingAs($admin)
        ->put(route('admin.learning-resource-types.update', $type), [
            'name' => 'Digital Module',
            'category' => 'Non-Print',
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
