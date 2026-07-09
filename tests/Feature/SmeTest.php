<?php

use App\Models\District;
use App\Models\Municipality;
use App\Models\School;
use App\Models\Sme;
use App\Models\SmeCatalogItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

function createSmeSchoolUser(): array
{
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    $school = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'is_activated' => true,
        'school_head' => 'Test Head',
        'email' => 'sme-school@example.com',
    ]);

    $user = User::factory()->schoolUser($school)->create();
    $school->update(['user_id' => $user->id]);

    return [$school, $user];
}

test('school user can register sme item with generated codes and opening movement', function () {
    [$school, $user] = createSmeSchoolUser();

    $this->actingAs($user)
        ->post(route('school.sme.store'), [
            'item_name' => 'Digital Microscope',
            'category' => 'Science',
            'brand' => 'AmScope',
            'condition' => 'Excellent',
            'status' => 'Available',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $sme = Sme::where('school_id', $school->id)->sole();

    expect($sme->item_code)->toStartWith('SME-');
    expect($sme->qr_code)->toBe($sme->item_code);

    $movement = $sme->movements()->sole();

    expect($movement->type)->toBe('created');
    expect($movement->user_id)->toBe($user->id);
});

test('school user can register sme item by selecting from the active catalog', function () {
    [$school, $user] = createSmeSchoolUser();

    $catalogItem = SmeCatalogItem::factory()->create([
        'item_name' => 'Catalog Microscope',
        'category' => 'Science',
        'brand' => 'AmScope',
        'model' => 'CS-100',
        'specifications' => 'Compound microscope',
        'manufacturer' => 'AmScope Philippines',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('school.sme.store'), [
            'sme_catalog_item_id' => $catalogItem->id,
            'item_name' => 'Changed Name',
            'category' => 'Mathematics',
            'brand' => 'Changed Brand',
            'condition' => 'Good',
            'status' => 'Available',
            'serial_number' => 'SN-123',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $sme = Sme::where('school_id', $school->id)->sole();

    expect($sme->sme_catalog_item_id)->toBe($catalogItem->id);
    expect($sme->item_name)->toBe('Catalog Microscope');
    expect($sme->category)->toBe('Science');
    expect($sme->brand)->toBe('AmScope');
    expect($sme->model)->toBe('CS-100');
    expect($sme->specifications)->toBe('Compound microscope');
    expect($sme->manufacturer)->toBe('AmScope Philippines');
    expect($sme->serial_number)->toBe('SN-123');
});

test('school user cannot register sme item from an inactive catalog item', function () {
    [, $user] = createSmeSchoolUser();

    $catalogItem = SmeCatalogItem::factory()->create([
        'is_active' => false,
    ]);

    $this->actingAs($user)
        ->post(route('school.sme.store'), [
            'sme_catalog_item_id' => $catalogItem->id,
            'item_name' => $catalogItem->item_name,
            'category' => $catalogItem->category,
            'condition' => 'Good',
            'status' => 'Available',
        ])
        ->assertSessionHasErrors('sme_catalog_item_id');
});

test('sme status, condition, assignment, and location changes are recorded as movements', function () {
    [$school, $user] = createSmeSchoolUser();

    $sme = Sme::factory()->create([
        'school_id' => $school->id,
        'status' => 'Available',
        'condition' => 'Good',
    ]);

    $this->actingAs($user)
        ->put(route('school.sme.update', $sme), [
            'item_code' => $sme->item_code,
            'item_name' => $sme->item_name,
            'category' => $sme->category,
            'condition' => 'Fair',
            'status' => 'In Use',
            'assigned_personnel' => 'Juan Dela Cruz',
            'current_location' => 'Science Laboratory',
            'movement_notes' => 'Deployed for the school year',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $sme->refresh();

    expect($sme->status)->toBe('In Use');
    expect($sme->condition)->toBe('Fair');

    $movements = $sme->movements()->pluck('type');

    expect($movements)->toContain('status_change');
    expect($movements)->toContain('condition_change');
    expect($movements)->toContain('reassigned');
    expect($movements)->toContain('relocated');

    $statusMovement = $sme->movements()->where('type', 'status_change')->sole();

    expect($statusMovement->from_value)->toBe('Available');
    expect($statusMovement->to_value)->toBe('In Use');
    expect($statusMovement->notes)->toBe('Deployed for the school year');
});

test('a school cannot modify another schools sme item', function () {
    [, $user] = createSmeSchoolUser();

    $otherSme = Sme::factory()->create();

    $this->actingAs($user)
        ->put(route('school.sme.update', $otherSme), [
            'item_name' => 'Hijacked',
            'category' => 'Science',
            'condition' => 'Good',
            'status' => 'Available',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('school.sme.destroy', $otherSme))
        ->assertForbidden();
});

test('deleting sme item soft deletes it and records the removal', function () {
    [$school, $user] = createSmeSchoolUser();

    $sme = Sme::factory()->create(['school_id' => $school->id]);

    $this->actingAs($user)
        ->delete(route('school.sme.destroy', $sme))
        ->assertRedirect();

    $this->assertSoftDeleted('sme', ['id' => $sme->id]);
    expect($sme->movements()->where('type', 'deleted')->exists())->toBeTrue();
});

test('sme category must be a valid science or math category', function () {
    [, $user] = createSmeSchoolUser();

    $this->actingAs($user)
        ->post(route('school.sme.store'), [
            'item_name' => 'Office Chair',
            'category' => 'Furniture',
            'condition' => 'Good',
            'status' => 'Available',
        ])
        ->assertSessionHasErrors('category');
});

test('school sme page renders with sme items and movements', function () {
    [$school, $user] = createSmeSchoolUser();

    Sme::factory()->count(2)->create(['school_id' => $school->id]);
    SmeCatalogItem::factory()->create(['item_name' => 'Visible Catalog Item']);
    SmeCatalogItem::factory()->create(['item_name' => 'Hidden Catalog Item', 'is_active' => false]);

    $this->actingAs($user)
        ->get(route('school.sme.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolSme')
            ->has('sme', 2)
            ->has('catalog', 1)
            ->where('catalog.0.item_name', 'Visible Catalog Item')
            ->has('categories')
            ->has('statuses')
        );
});

test('admin can browse and filter division-wide sme items', function () {
    $admin = User::factory()->admin()->create();

    $school = School::factory()->create(['school_name' => 'Filter Test School']);

    Sme::factory()->create([
        'school_id' => $school->id,
        'item_name' => 'Microscope',
        'category' => 'Science',
        'status' => 'Available',
    ]);
    Sme::factory()->create([
        'school_id' => $school->id,
        'item_name' => 'Manipulative Kit',
        'category' => 'Mathematics',
        'status' => 'In Use',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.sme.index', ['category' => 'Science']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminSme')
            ->has('sme.data', 1)
            ->where('sme.data.0.item_name', 'Microscope')
            ->where('summary.total', 2)
        );
});

test('school users cannot access the admin sme page', function () {
    [, $user] = createSmeSchoolUser();

    $this->actingAs($user)
        ->get(route('admin.sme.index'))
        ->assertForbidden();
});

test('admin can manage the sme catalog', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.sme-catalog.store'), [
            'item_name' => 'Telescope',
            'category' => 'Science',
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $catalogItem = SmeCatalogItem::where('item_name', 'Telescope')->sole();

    $this->actingAs($admin)
        ->put(route('admin.sme-catalog.update', $catalogItem), [
            'item_name' => 'Telescope',
            'category' => 'Science',
            'is_active' => false,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($catalogItem->refresh()->is_active)->toBeFalse();

    $this->actingAs($admin)
        ->delete(route('admin.sme-catalog.destroy', $catalogItem))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('sme_catalog_items', ['id' => $catalogItem->id]);
});

test('admin cannot delete a sme catalog item already used by a school', function () {
    $admin = User::factory()->admin()->create();

    $catalogItem = SmeCatalogItem::factory()->create();
    Sme::factory()->create(['sme_catalog_item_id' => $catalogItem->id]);

    $this->actingAs($admin)
        ->delete(route('admin.sme-catalog.destroy', $catalogItem))
        ->assertSessionHasErrors('sme_catalog_item');

    $this->assertDatabaseHas('sme_catalog_items', ['id' => $catalogItem->id]);
});

test('admin can import sme catalog items via csv', function () {
    $admin = User::factory()->admin()->create();

    $csv = UploadedFile::fake()->createWithContent('sme-catalog.csv', implode("\n", [
        'item_name,category,brand,model,specifications,manufacturer,description,is_active',
        'Telescope,Science,Celestron,AstroMaster,Reflector telescope,Celestron Inc,Science equipment,1',
    ]));

    $this->actingAs($admin)
        ->postJson(route('admin.sme-catalog.import.store'), ['file' => $csv])
        ->assertOk()
        ->assertJsonPath('summary.imported', 1);

    $this->assertDatabaseHas('sme_catalog_items', ['item_name' => 'Telescope', 'category' => 'Science']);
});

test('admin can download the sme catalog import template', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.sme-catalog.import.template'))
        ->assertOk()
        ->assertDownload('sme-catalog-import-template.csv');
});

test('admin can export sme summary as csv', function () {
    $admin = User::factory()->admin()->create();

    $school = School::factory()->create();
    Sme::factory()->create([
        'school_id' => $school->id,
        'category' => 'Science',
        'condition' => 'Good',
        'status' => 'Available',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reports.sme.export'))
        ->assertOk()
        ->assertDownload('sme-summary.csv');
});
