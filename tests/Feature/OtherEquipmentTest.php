<?php

use App\Models\District;
use App\Models\Municipality;
use App\Models\OtherEquipment;
use App\Models\OtherEquipmentCatalogItem;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

function createOtherEquipmentSchoolUser(): array
{
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    $school = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'is_activated' => true,
        'school_head' => 'Test Head',
        'email' => 'other-equipment-school@example.com',
    ]);

    $user = User::factory()->schoolUser($school)->create();
    $school->update(['user_id' => $user->id]);

    return [$school, $user];
}

test('school user can register other equipment with generated codes and opening movement', function () {
    [$school, $user] = createOtherEquipmentSchoolUser();

    $this->actingAs($user)
        ->post(route('school.other-equipment.store'), [
            'item_name' => 'Basketball Set',
            'category' => 'Sports',
            'brand' => 'Molten',
            'condition' => 'Excellent',
            'status' => 'Available',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $equipment = OtherEquipment::where('school_id', $school->id)->sole();

    expect($equipment->item_code)->toStartWith('OTH-');
    expect($equipment->qr_code)->toBe($equipment->item_code);

    $movement = $equipment->movements()->sole();

    expect($movement->type)->toBe('created');
    expect($movement->user_id)->toBe($user->id);
});

test('school user can register other equipment by selecting from the active catalog', function () {
    [$school, $user] = createOtherEquipmentSchoolUser();

    $catalogItem = OtherEquipmentCatalogItem::factory()->create([
        'item_name' => 'Catalog Tool Kit',
        'category' => 'TVL',
        'brand' => 'Stanley',
        'model' => 'TK-100',
        'specifications' => 'Workshop tool kit',
        'manufacturer' => 'Stanley Philippines',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('school.other-equipment.store'), [
            'other_equipment_catalog_item_id' => $catalogItem->id,
            'item_name' => 'Changed Name',
            'category' => 'Library',
            'brand' => 'Changed Brand',
            'condition' => 'Good',
            'status' => 'Available',
            'serial_number' => 'SN-123',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $equipment = OtherEquipment::where('school_id', $school->id)->sole();

    expect($equipment->other_equipment_catalog_item_id)->toBe($catalogItem->id);
    expect($equipment->item_name)->toBe('Catalog Tool Kit');
    expect($equipment->category)->toBe('TVL');
    expect($equipment->brand)->toBe('Stanley');
    expect($equipment->model)->toBe('TK-100');
    expect($equipment->specifications)->toBe('Workshop tool kit');
    expect($equipment->manufacturer)->toBe('Stanley Philippines');
    expect($equipment->serial_number)->toBe('SN-123');
});

test('school user cannot register other equipment from an inactive catalog item', function () {
    [, $user] = createOtherEquipmentSchoolUser();

    $catalogItem = OtherEquipmentCatalogItem::factory()->create([
        'is_active' => false,
    ]);

    $this->actingAs($user)
        ->post(route('school.other-equipment.store'), [
            'other_equipment_catalog_item_id' => $catalogItem->id,
            'item_name' => $catalogItem->item_name,
            'category' => $catalogItem->category,
            'condition' => 'Good',
            'status' => 'Available',
        ])
        ->assertSessionHasErrors('other_equipment_catalog_item_id');
});

test('other equipment status, condition, assignment, and location changes are recorded as movements', function () {
    [$school, $user] = createOtherEquipmentSchoolUser();

    $equipment = OtherEquipment::factory()->create([
        'school_id' => $school->id,
        'status' => 'Available',
        'condition' => 'Good',
    ]);

    $this->actingAs($user)
        ->put(route('school.other-equipment.update', $equipment), [
            'item_code' => $equipment->item_code,
            'item_name' => $equipment->item_name,
            'category' => $equipment->category,
            'condition' => 'Fair',
            'status' => 'In Use',
            'assigned_personnel' => 'Juan Dela Cruz',
            'current_location' => 'Workshop',
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

test('a school cannot modify another schools other equipment', function () {
    [, $user] = createOtherEquipmentSchoolUser();

    $otherEquipment = OtherEquipment::factory()->create();

    $this->actingAs($user)
        ->put(route('school.other-equipment.update', $otherEquipment), [
            'item_name' => 'Hijacked',
            'category' => 'TVL',
            'condition' => 'Good',
            'status' => 'Available',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('school.other-equipment.destroy', $otherEquipment))
        ->assertForbidden();
});

test('deleting other equipment soft deletes it and records the removal', function () {
    [$school, $user] = createOtherEquipmentSchoolUser();

    $equipment = OtherEquipment::factory()->create(['school_id' => $school->id]);

    $this->actingAs($user)
        ->delete(route('school.other-equipment.destroy', $equipment))
        ->assertRedirect();

    $this->assertSoftDeleted('other_equipment', ['id' => $equipment->id]);
    expect($equipment->movements()->where('type', 'deleted')->exists())->toBeTrue();
});

test('other equipment category must be a valid other equipment category', function () {
    [, $user] = createOtherEquipmentSchoolUser();

    $this->actingAs($user)
        ->post(route('school.other-equipment.store'), [
            'item_name' => 'Laptop',
            'category' => 'Laptop',
            'condition' => 'Good',
            'status' => 'Available',
        ])
        ->assertSessionHasErrors('category');
});

test('school other equipment page renders with equipment and movements', function () {
    [$school, $user] = createOtherEquipmentSchoolUser();

    OtherEquipment::factory()->count(2)->create(['school_id' => $school->id]);
    OtherEquipmentCatalogItem::factory()->create(['item_name' => 'Visible Catalog Item']);
    OtherEquipmentCatalogItem::factory()->create(['item_name' => 'Hidden Catalog Item', 'is_active' => false]);

    $this->actingAs($user)
        ->get(route('school.other-equipment.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolOtherEquipment')
            ->has('equipment', 2)
            ->has('catalog', 1)
            ->where('catalog.0.item_name', 'Visible Catalog Item')
            ->has('categories')
            ->has('statuses')
        );
});

test('admin can browse and filter division-wide other equipment', function () {
    $admin = User::factory()->admin()->create();

    $school = School::factory()->create(['school_name' => 'Filter Test School']);

    OtherEquipment::factory()->create([
        'school_id' => $school->id,
        'item_name' => 'Drill Press',
        'category' => 'TVL',
        'status' => 'Available',
    ]);
    OtherEquipment::factory()->create([
        'school_id' => $school->id,
        'item_name' => 'Assistive Device',
        'category' => 'SPED',
        'status' => 'In Use',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.other-equipment.index', ['category' => 'TVL']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminOtherEquipment')
            ->has('equipment.data', 1)
            ->where('equipment.data.0.item_name', 'Drill Press')
            ->where('summary.total', 2)
        );
});

test('school users cannot access the admin other equipment page', function () {
    [, $user] = createOtherEquipmentSchoolUser();

    $this->actingAs($user)
        ->get(route('admin.other-equipment.index'))
        ->assertForbidden();
});

test('admin can manage the other equipment catalog', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.other-equipment-catalog.store'), [
            'item_name' => 'Volleyball Set',
            'category' => 'Sports',
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $catalogItem = OtherEquipmentCatalogItem::where('item_name', 'Volleyball Set')->sole();

    $this->actingAs($admin)
        ->put(route('admin.other-equipment-catalog.update', $catalogItem), [
            'item_name' => 'Volleyball Set',
            'category' => 'Sports',
            'is_active' => false,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($catalogItem->refresh()->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->delete(route('admin.other-equipment-catalog.destroy', $catalogItem))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('other_equipment_catalog_items', ['id' => $catalogItem->id]);
});

test('admin cannot delete an other equipment catalog item already used by a school', function () {
    $admin = User::factory()->admin()->create();

    $catalogItem = OtherEquipmentCatalogItem::factory()->create();
    OtherEquipment::factory()->create(['other_equipment_catalog_item_id' => $catalogItem->id]);

    $this->actingAs($admin)
        ->delete(route('admin.other-equipment-catalog.destroy', $catalogItem))
        ->assertSessionHasErrors('other_equipment_catalog_item');

    $this->assertDatabaseHas('other_equipment_catalog_items', ['id' => $catalogItem->id]);
});

test('admin can import other equipment catalog items via csv', function () {
    $admin = User::factory()->admin()->create();

    $csv = UploadedFile::fake()->createWithContent('other-equipment-catalog.csv', implode("\n", [
        'item_name,category,brand,model,specifications,manufacturer,description,is_active',
        'Volleyball Set,Sports,Mikasa,V200W,Indoor volleyball set,Mikasa Sports,Sports equipment,1',
    ]));

    $this->actingAs($admin)
        ->postJson(route('admin.other-equipment-catalog.import.store'), ['file' => $csv])
        ->assertOk()
        ->assertJsonPath('summary.imported', 1);

    $this->assertDatabaseHas('other_equipment_catalog_items', ['item_name' => 'Volleyball Set', 'category' => 'Sports']);
});

test('admin can download the other equipment catalog import template', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.other-equipment-catalog.import.template'))
        ->assertOk()
        ->assertDownload('other-equipment-catalog-import-template.csv');
});

test('admin can export other equipment summary as csv', function () {
    $admin = User::factory()->admin()->create();

    $school = School::factory()->create();
    OtherEquipment::factory()->create([
        'school_id' => $school->id,
        'category' => 'TVL',
        'condition' => 'Good',
        'status' => 'Available',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reports.other-equipment.export'))
        ->assertOk()
        ->assertDownload('other-equipment-summary.csv');
});
