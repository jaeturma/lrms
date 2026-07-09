<?php

use App\Models\District;
use App\Models\IctEquipment;
use App\Models\IctEquipmentCatalogItem;
use App\Models\Municipality;
use App\Models\OtherEquipment;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

function createIctEquipmentSchoolUser(): array
{
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    $school = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'is_activated' => true,
        'school_head' => 'Test Head',
        'email' => 'ict-equipment-school@example.com',
    ]);

    $user = User::factory()->schoolUser($school)->create();
    $school->update(['user_id' => $user->id]);

    return [$school, $user];
}

test('school user can register ict equipment with generated codes and opening movement', function () {
    [$school, $user] = createIctEquipmentSchoolUser();

    $this->actingAs($user)
        ->post(route('school.ict-equipment.store'), [
            'item_name' => 'Smart TV 55"',
            'category' => 'Smart TV',
            'brand' => 'Samsung',
            'condition' => 'Excellent',
            'status' => 'Available',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $equipment = IctEquipment::where('school_id', $school->id)->sole();

    expect($equipment->item_code)->toStartWith('ICT-');
    expect($equipment->qr_code)->toBe($equipment->item_code);

    $movement = $equipment->movements()->sole();

    expect($movement->type)->toBe('created');
    expect($movement->user_id)->toBe($user->id);
});

test('school user can register ict equipment by selecting from the active catalog', function () {
    [$school, $user] = createIctEquipmentSchoolUser();

    $catalogItem = IctEquipmentCatalogItem::factory()->create([
        'item_name' => 'Catalog Projector',
        'category' => 'Projector',
        'brand' => 'Epson',
        'model' => 'EB-X49',
        'specifications' => 'Classroom LCD projector',
        'manufacturer' => 'Epson Philippines',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('school.ict-equipment.store'), [
            'ict_equipment_catalog_item_id' => $catalogItem->id,
            'item_name' => 'Changed Name',
            'category' => 'Laptop',
            'brand' => 'Changed Brand',
            'condition' => 'Good',
            'status' => 'Available',
            'serial_number' => 'SN-123',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $equipment = IctEquipment::where('school_id', $school->id)->sole();

    expect($equipment->ict_equipment_catalog_item_id)->toBe($catalogItem->id);
    expect($equipment->item_name)->toBe('Catalog Projector');
    expect($equipment->category)->toBe('Projector');
    expect($equipment->brand)->toBe('Epson');
    expect($equipment->model)->toBe('EB-X49');
    expect($equipment->specifications)->toBe('Classroom LCD projector');
    expect($equipment->manufacturer)->toBe('Epson Philippines');
    expect($equipment->serial_number)->toBe('SN-123');
});

test('school user cannot register ict equipment from an inactive catalog item', function () {
    [, $user] = createIctEquipmentSchoolUser();

    $catalogItem = IctEquipmentCatalogItem::factory()->create([
        'is_active' => false,
    ]);

    $this->actingAs($user)
        ->post(route('school.ict-equipment.store'), [
            'ict_equipment_catalog_item_id' => $catalogItem->id,
            'item_name' => $catalogItem->item_name,
            'category' => $catalogItem->category,
            'condition' => 'Good',
            'status' => 'Available',
        ])
        ->assertSessionHasErrors('ict_equipment_catalog_item_id');
});

test('ict equipment status, condition, assignment, and location changes are recorded as movements', function () {
    [$school, $user] = createIctEquipmentSchoolUser();

    $equipment = IctEquipment::factory()->create([
        'school_id' => $school->id,
        'status' => 'Available',
        'condition' => 'Good',
    ]);

    $this->actingAs($user)
        ->put(route('school.ict-equipment.update', $equipment), [
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

test('a school cannot modify another schools ict equipment', function () {
    [, $user] = createIctEquipmentSchoolUser();

    $otherEquipment = IctEquipment::factory()->create();

    $this->actingAs($user)
        ->put(route('school.ict-equipment.update', $otherEquipment), [
            'item_name' => 'Hijacked',
            'category' => 'Laptop',
            'condition' => 'Good',
            'status' => 'Available',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('school.ict-equipment.destroy', $otherEquipment))
        ->assertForbidden();
});

test('deleting ict equipment soft deletes it and records the removal', function () {
    [$school, $user] = createIctEquipmentSchoolUser();

    $equipment = IctEquipment::factory()->create(['school_id' => $school->id]);

    $this->actingAs($user)
        ->delete(route('school.ict-equipment.destroy', $equipment))
        ->assertRedirect();

    $this->assertSoftDeleted('ict_equipment', ['id' => $equipment->id]);
    expect($equipment->movements()->where('type', 'deleted')->exists())->toBeTrue();
});

test('ict equipment category must be a valid ict category', function () {
    [, $user] = createIctEquipmentSchoolUser();

    $this->actingAs($user)
        ->post(route('school.ict-equipment.store'), [
            'item_name' => 'Tool Kit',
            'category' => 'TVL',
            'condition' => 'Good',
            'status' => 'Available',
        ])
        ->assertSessionHasErrors('category');
});

test('school ict equipment page renders with equipment and movements', function () {
    [$school, $user] = createIctEquipmentSchoolUser();

    IctEquipment::factory()->count(2)->create(['school_id' => $school->id]);
    IctEquipmentCatalogItem::factory()->create(['item_name' => 'Visible Catalog Item']);
    IctEquipmentCatalogItem::factory()->create(['item_name' => 'Hidden Catalog Item', 'is_active' => false]);

    $this->actingAs($user)
        ->get(route('school.ict-equipment.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolIctEquipment')
            ->has('equipment', 2)
            ->has('catalog', 1)
            ->where('catalog.0.item_name', 'Visible Catalog Item')
            ->has('categories')
            ->has('statuses')
        );
});

test('admin can browse and filter division-wide ict equipment', function () {
    $admin = User::factory()->admin()->create();

    $school = School::factory()->create(['school_name' => 'Filter Test School']);

    IctEquipment::factory()->create([
        'school_id' => $school->id,
        'item_name' => 'Tablet',
        'category' => 'Tablet',
        'status' => 'Available',
    ]);
    IctEquipment::factory()->create([
        'school_id' => $school->id,
        'item_name' => 'Laptop',
        'category' => 'Laptop',
        'status' => 'In Use',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.ict-equipment.index', ['category' => 'Tablet']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminIctEquipment')
            ->has('equipment.data', 1)
            ->where('equipment.data.0.item_name', 'Tablet')
            ->where('summary.total', 2)
        );
});

test('admin dashboard includes total equipment stat combining ict and other equipment', function () {
    $admin = User::factory()->admin()->create();

    IctEquipment::factory()->count(2)->create();
    OtherEquipment::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminDashboard')
            ->where('stats.total_equipment', 5)
        );
});

test('school users cannot access the admin ict equipment page', function () {
    [, $user] = createIctEquipmentSchoolUser();

    $this->actingAs($user)
        ->get(route('admin.ict-equipment.index'))
        ->assertForbidden();
});

test('admin can manage the ict equipment catalog', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.ict-equipment-catalog.store'), [
            'item_name' => 'Document Camera',
            'category' => 'Projector',
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $catalogItem = IctEquipmentCatalogItem::where('item_name', 'Document Camera')->sole();

    $this->actingAs($admin)
        ->put(route('admin.ict-equipment-catalog.update', $catalogItem), [
            'item_name' => 'Document Camera',
            'category' => 'Projector',
            'is_active' => false,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($catalogItem->refresh()->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->delete(route('admin.ict-equipment-catalog.destroy', $catalogItem))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('ict_equipment_catalog_items', ['id' => $catalogItem->id]);
});

test('admin cannot delete an ict equipment catalog item already used by a school', function () {
    $admin = User::factory()->admin()->create();

    $catalogItem = IctEquipmentCatalogItem::factory()->create();
    IctEquipment::factory()->create(['ict_equipment_catalog_item_id' => $catalogItem->id]);

    $this->actingAs($admin)
        ->delete(route('admin.ict-equipment-catalog.destroy', $catalogItem))
        ->assertSessionHasErrors('ict_equipment_catalog_item');

    $this->assertDatabaseHas('ict_equipment_catalog_items', ['id' => $catalogItem->id]);
});

test('admin can import ict equipment catalog items via csv', function () {
    $admin = User::factory()->admin()->create();

    $csv = UploadedFile::fake()->createWithContent('ict-equipment-catalog.csv', implode("\n", [
        'item_name,category,brand,model,specifications,manufacturer,description,is_active',
        'Document Camera,Projector,Elmo,MX-1,Classroom document camera,Elmo Inc,ICT equipment,1',
    ]));

    $this->actingAs($admin)
        ->postJson(route('admin.ict-equipment-catalog.import.store'), ['file' => $csv])
        ->assertOk()
        ->assertJsonPath('summary.imported', 1);

    $this->assertDatabaseHas('ict_equipment_catalog_items', ['item_name' => 'Document Camera', 'category' => 'Projector']);
});

test('admin can download the ict equipment catalog import template', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.ict-equipment-catalog.import.template'))
        ->assertOk()
        ->assertDownload('ict-equipment-catalog-import-template.csv');
});

test('admin can export ict equipment summary as csv', function () {
    $admin = User::factory()->admin()->create();

    $school = School::factory()->create();
    IctEquipment::factory()->create([
        'school_id' => $school->id,
        'category' => 'Laptop',
        'condition' => 'Good',
        'status' => 'Available',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reports.ict-equipment.export'))
        ->assertOk()
        ->assertDownload('ict-equipment-summary.csv');
});
