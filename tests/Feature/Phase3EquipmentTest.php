<?php

use App\Models\District;
use App\Models\Equipment;
use App\Models\Municipality;
use App\Models\School;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function createEquipmentSchoolUser(): array
{
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    $school = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'is_activated' => true,
        'school_head' => 'Test Head',
        'email' => 'equipment-school@example.com',
    ]);

    $user = User::factory()->schoolUser($school)->create();
    $school->update(['user_id' => $user->id]);

    return [$school, $user];
}

test('school user can register equipment with generated codes and opening movement', function () {
    [$school, $user] = createEquipmentSchoolUser();

    $this->actingAs($user)
        ->post(route('school.equipment.store'), [
            'item_name' => 'Smart TV 55"',
            'category' => 'ICT',
            'brand' => 'Samsung',
            'condition' => 'Excellent',
            'status' => 'Available',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $equipment = Equipment::where('school_id', $school->id)->sole();

    expect($equipment->item_code)->toStartWith('EQP-');
    expect($equipment->qr_code)->toBe($equipment->item_code);

    $movement = $equipment->movements()->sole();

    expect($movement->type)->toBe('created');
    expect($movement->user_id)->toBe($user->id);
});

test('status, condition, assignment, and location changes are recorded as movements', function () {
    [$school, $user] = createEquipmentSchoolUser();

    $equipment = Equipment::factory()->create([
        'school_id' => $school->id,
        'status' => 'Available',
        'condition' => 'Good',
    ]);

    $this->actingAs($user)
        ->put(route('school.equipment.update', $equipment), [
            'item_code' => $equipment->item_code,
            'item_name' => $equipment->item_name,
            'category' => $equipment->category,
            'condition' => 'Fair',
            'status' => 'In Use',
            'assigned_personnel' => 'Juan Dela Cruz',
            'current_location' => 'Computer Laboratory',
            'movement_notes' => 'Deployed for the school year',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $equipment->refresh();

    expect($equipment->status)->toBe('In Use');
    expect($equipment->condition)->toBe('Fair');

    $movements = $equipment->movements()->pluck('type');

    expect($movements)->toContain('status_change');
    expect($movements)->toContain('condition_change');
    expect($movements)->toContain('reassigned');
    expect($movements)->toContain('relocated');

    $statusMovement = $equipment->movements()->where('type', 'status_change')->sole();

    expect($statusMovement->from_value)->toBe('Available');
    expect($statusMovement->to_value)->toBe('In Use');
    expect($statusMovement->notes)->toBe('Deployed for the school year');
});

test('a school cannot modify another schools equipment', function () {
    [, $user] = createEquipmentSchoolUser();

    $otherEquipment = Equipment::factory()->create();

    $this->actingAs($user)
        ->put(route('school.equipment.update', $otherEquipment), [
            'item_name' => 'Hijacked',
            'category' => 'ICT',
            'condition' => 'Good',
            'status' => 'Available',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('school.equipment.destroy', $otherEquipment))
        ->assertForbidden();
});

test('deleting equipment soft deletes it and records the removal', function () {
    [$school, $user] = createEquipmentSchoolUser();

    $equipment = Equipment::factory()->create(['school_id' => $school->id]);

    $this->actingAs($user)
        ->delete(route('school.equipment.destroy', $equipment))
        ->assertRedirect();

    $this->assertSoftDeleted('equipment', ['id' => $equipment->id]);
    expect($equipment->movements()->where('type', 'deleted')->exists())->toBeTrue();
});

test('equipment category must be a valid learning equipment category', function () {
    [, $user] = createEquipmentSchoolUser();

    $this->actingAs($user)
        ->post(route('school.equipment.store'), [
            'item_name' => 'Office Chair',
            'category' => 'Furniture',
            'condition' => 'Good',
            'status' => 'Available',
        ])
        ->assertSessionHasErrors('category');
});

test('school equipment page renders with equipment and movements', function () {
    [$school, $user] = createEquipmentSchoolUser();

    Equipment::factory()->count(2)->create(['school_id' => $school->id]);

    $this->actingAs($user)
        ->get(route('school.equipment.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolEquipment')
            ->has('equipment', 2)
            ->has('categories')
            ->has('statuses')
        );
});

test('admin can browse and filter division-wide equipment', function () {
    $admin = User::factory()->admin()->create();

    $school = School::factory()->create(['school_name' => 'Filter Test School']);

    Equipment::factory()->create([
        'school_id' => $school->id,
        'item_name' => 'Microscope',
        'category' => 'Science',
        'status' => 'Available',
    ]);
    Equipment::factory()->create([
        'school_id' => $school->id,
        'item_name' => 'Laptop',
        'category' => 'ICT',
        'status' => 'In Use',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.equipment.index', ['category' => 'Science']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminEquipment')
            ->has('equipment.data', 1)
            ->where('equipment.data.0.item_name', 'Microscope')
            ->where('summary.total', 2)
        );
});

test('admin dashboard includes total equipment stat', function () {
    $admin = User::factory()->admin()->create();

    Equipment::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminDashboard')
            ->where('stats.total_equipment', 3)
        );
});

test('school users cannot access the admin equipment page', function () {
    [, $user] = createEquipmentSchoolUser();

    $this->actingAs($user)
        ->get(route('admin.equipment.index'))
        ->assertForbidden();
});
