<?php

use App\Models\District;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Municipality;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function createActivatedSchoolUser(): array
{
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    $school = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'is_activated' => true,
        'school_head' => 'Test Head',
        'email' => 'school-head@example.com',
    ]);

    $user = User::factory()->schoolUser($school)->create();

    $school->update(['user_id' => $user->id]);

    return [$school, $user];
}

test('admin can create school years and first one becomes active', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.school-years.store'), [
            'name' => '2026-2027',
            'starts_on' => '2026-06-01',
            'ends_on' => '2027-04-30',
        ])
        ->assertRedirect();

    expect(SchoolYear::where('name', '2026-2027')->firstOrFail()->is_active)->toBeTrue();

    $this->actingAs($admin)
        ->post(route('admin.school-years.store'), [
            'name' => '2027-2028',
        ])
        ->assertRedirect();

    expect(SchoolYear::where('name', '2027-2028')->firstOrFail()->is_active)->toBeFalse();
});

test('activating a school year deactivates the others', function () {
    $admin = User::factory()->admin()->create();
    $current = SchoolYear::factory()->active()->create();
    $next = SchoolYear::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.school-years.activate', $next))
        ->assertRedirect();

    expect($next->fresh()->is_active)->toBeTrue();
    expect($current->fresh()->is_active)->toBeFalse();
});

test('school year with enrollments or active flag cannot be deleted', function () {
    $admin = User::factory()->admin()->create();

    $active = SchoolYear::factory()->active()->create();
    $this->actingAs($admin)
        ->delete(route('admin.school-years.destroy', $active))
        ->assertSessionHasErrors('name');

    $withData = SchoolYear::factory()->create();
    Enrollment::factory()->create(['school_year_id' => $withData->id]);

    $this->actingAs($admin)
        ->delete(route('admin.school-years.destroy', $withData))
        ->assertSessionHasErrors('name');

    expect(SchoolYear::count())->toBe(2);
});

test('admin can manage grade levels and deletion is blocked when in use', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.grade-levels.store'), [
            'name' => 'Grade 7',
            'sort_order' => 7,
        ])
        ->assertRedirect();

    $gradeLevel = GradeLevel::where('name', 'Grade 7')->firstOrFail();

    Enrollment::factory()->create(['grade_level_id' => $gradeLevel->id]);

    $this->actingAs($admin)
        ->delete(route('admin.grade-levels.destroy', $gradeLevel))
        ->assertSessionHasErrors('name');

    expect(GradeLevel::whereKey($gradeLevel->id)->exists())->toBeTrue();
});

test('school user can save enrollment for the active school year', function () {
    [$school, $user] = createActivatedSchoolUser();

    $schoolYear = SchoolYear::factory()->active()->create();
    $kinder = GradeLevel::factory()->create(['name' => 'Kindergarten', 'sort_order' => 0]);
    $gradeOne = GradeLevel::factory()->create(['name' => 'Grade 1', 'sort_order' => 1]);

    $this->actingAs($user)
        ->put(route('school.enrollment.store'), [
            'enrollments' => [
                ['grade_level_id' => $kinder->id, 'male_count' => 12, 'female_count' => 15],
                ['grade_level_id' => $gradeOne->id, 'male_count' => 0, 'female_count' => 0],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $enrollments = Enrollment::where('school_id', $school->id)
        ->where('school_year_id', $schoolYear->id)
        ->get();

    expect($enrollments)->toHaveCount(1);
    expect($enrollments->first()->grade_level_id)->toBe($kinder->id);
    expect($enrollments->first()->male_count)->toBe(12);
    expect($enrollments->first()->female_count)->toBe(15);
});

test('saving enrollment replaces previous entries for the same school year', function () {
    [$school, $user] = createActivatedSchoolUser();

    $schoolYear = SchoolYear::factory()->active()->create();
    $kinder = GradeLevel::factory()->create(['name' => 'Kindergarten', 'sort_order' => 0]);

    Enrollment::factory()->create([
        'school_id' => $school->id,
        'school_year_id' => $schoolYear->id,
        'grade_level_id' => $kinder->id,
        'male_count' => 5,
        'female_count' => 5,
    ]);

    $this->actingAs($user)
        ->put(route('school.enrollment.store'), [
            'enrollments' => [
                ['grade_level_id' => $kinder->id, 'male_count' => 20, 'female_count' => 22],
            ],
        ])
        ->assertRedirect();

    $enrollment = Enrollment::where('school_id', $school->id)->sole();

    expect($enrollment->male_count)->toBe(20);
    expect($enrollment->female_count)->toBe(22);
});

test('enrollment saving fails without an active school year', function () {
    [, $user] = createActivatedSchoolUser();

    $gradeLevel = GradeLevel::factory()->create();

    $this->actingAs($user)
        ->put(route('school.enrollment.store'), [
            'enrollments' => [
                ['grade_level_id' => $gradeLevel->id, 'male_count' => 10, 'female_count' => 10],
            ],
        ])
        ->assertSessionHasErrors('enrollments');

    expect(Enrollment::count())->toBe(0);
});

test('school user can view the enrollment page', function () {
    [, $user] = createActivatedSchoolUser();

    SchoolYear::factory()->active()->create(['name' => '2026-2027']);
    GradeLevel::factory()->create(['name' => 'Kindergarten', 'sort_order' => 0]);

    $this->actingAs($user)
        ->get(route('school.enrollment.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolEnrollment')
            ->where('activeSchoolYear.name', '2026-2027')
            ->has('gradeLevels', 1)
        );
});

test('admin users cannot access school enrollment routes', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('school.enrollment.index'))
        ->assertForbidden();
});

test('admin dashboard reports total learners for the active school year', function () {
    $admin = User::factory()->admin()->create();

    $activeYear = SchoolYear::factory()->active()->create();
    $otherYear = SchoolYear::factory()->create();

    Enrollment::factory()->create([
        'school_year_id' => $activeYear->id,
        'male_count' => 40,
        'female_count' => 60,
    ]);
    Enrollment::factory()->create([
        'school_year_id' => $otherYear->id,
        'male_count' => 999,
        'female_count' => 999,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminDashboard')
            ->where('stats.total_learners', 100)
        );
});

test('learning resource type requires a valid category', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.learning-resource-types.store'), [
            'name' => 'Hologram',
            'category' => 'Imaginary',
        ])
        ->assertSessionHasErrors('category');
});
