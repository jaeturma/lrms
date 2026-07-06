<?php

use App\Models\District;
use App\Models\InventoryMovement;
use App\Models\LearningResource;
use App\Models\LearningResourceType;
use App\Models\Municipality;
use App\Models\School;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function createSchoolUserWithType(): array
{
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    $school = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'is_activated' => true,
        'school_head' => 'Test Head',
        'email' => 'inventory-school@example.com',
    ]);

    $user = User::factory()->schoolUser($school)->create();
    $school->update(['user_id' => $user->id]);

    $type = LearningResourceType::factory()->create(['name' => 'Textbook']);

    return [$school, $user, $type];
}

function encodeResource(User $user, LearningResourceType $type, int $delivered = 30, int $defect = 2): LearningResource
{
    test()->actingAs($user)->putJson(route('school.resources.store'), [
        'resources' => [
            [
                'learning_resource_type_id' => $type->id,
                'title' => 'Science 6 Textbook',
                'publisher' => 'DepEd Press',
                'quantity_delivered' => $delivered,
                'quantity_with_issue_defect' => $defect,
                'remarks' => null,
            ],
        ],
    ])->assertOk();

    return LearningResource::where('title', 'Science 6 Textbook')->firstOrFail();
}

test('encoding a learning resource creates its inventory and opening movements', function () {
    [, $user, $type] = createSchoolUserWithType();

    $resource = encodeResource($user, $type, 30, 2);

    expect($resource->inventory->available)->toBe(28);
    expect($resource->inventory->damaged)->toBe(2);

    $movements = $resource->inventoryMovements()->orderBy('id')->get();

    expect($movements)->toHaveCount(2);
    expect($movements[0]->type)->toBe('received');
    expect($movements[0]->quantity)->toBe(30);
    expect($movements[1]->type)->toBe('damaged');
    expect($movements[1]->quantity)->toBe(2);
});

test('saving again with the same id updates the row instead of recreating it', function () {
    [$school, $user, $type] = createSchoolUserWithType();

    $resource = encodeResource($user, $type, 30, 2);

    $this->actingAs($user)->putJson(route('school.resources.store'), [
        'resources' => [
            [
                'id' => $resource->id,
                'learning_resource_type_id' => $type->id,
                'title' => 'Science 6 Textbook',
                'publisher' => 'DepEd Press',
                'quantity_delivered' => 40,
                'quantity_with_issue_defect' => 2,
                'remarks' => null,
            ],
        ],
    ])->assertOk();

    expect(LearningResource::where('school_id', $school->id)->count())->toBe(1);

    $resource->refresh();

    expect($resource->quantity_delivered)->toBe(40);
    expect($resource->inventory->available)->toBe(38);
    expect($resource->inventoryMovements()->where('type', 'adjustment')->count())->toBe(1);
});

test('school user can record an issue movement', function () {
    [, $user, $type] = createSchoolUserWithType();

    $resource = encodeResource($user, $type, 30, 0);

    $this->actingAs($user)
        ->post(route('school.inventory.movements.store', $resource), [
            'type' => 'issued',
            'quantity' => 10,
            'notes' => 'Issued to Grade 6 adviser',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $resource->refresh();

    expect($resource->inventory->available)->toBe(20);
    expect($resource->inventory->issued)->toBe(10);

    $movement = $resource->inventoryMovements()->where('type', 'issued')->sole();

    expect($movement->from_status)->toBe('available');
    expect($movement->to_status)->toBe('issued');
    expect($movement->user_id)->toBe($user->id);
});

test('movements cannot exceed the source status quantity', function () {
    [, $user, $type] = createSchoolUserWithType();

    $resource = encodeResource($user, $type, 5, 0);

    $this->actingAs($user)
        ->post(route('school.inventory.movements.store', $resource), [
            'type' => 'issued',
            'quantity' => 10,
        ])
        ->assertSessionHasErrors('quantity');

    expect($resource->inventory->fresh()->available)->toBe(5);
});

test('returned movement moves issued copies back to available', function () {
    [, $user, $type] = createSchoolUserWithType();

    $resource = encodeResource($user, $type, 30, 0);

    $this->actingAs($user)->post(route('school.inventory.movements.store', $resource), [
        'type' => 'issued',
        'quantity' => 10,
    ]);

    $this->actingAs($user)
        ->post(route('school.inventory.movements.store', $resource), [
            'type' => 'returned',
            'quantity' => 4,
            'from_status' => 'issued',
        ])
        ->assertSessionHasNoErrors();

    $inventory = $resource->inventory->fresh();

    expect($inventory->available)->toBe(24);
    expect($inventory->issued)->toBe(6);
});

test('encoding cannot reduce quantities below copies already accounted for', function () {
    [, $user, $type] = createSchoolUserWithType();

    $resource = encodeResource($user, $type, 30, 0);

    $this->actingAs($user)->post(route('school.inventory.movements.store', $resource), [
        'type' => 'issued',
        'quantity' => 25,
    ]);

    $this->actingAs($user)->putJson(route('school.resources.store'), [
        'resources' => [
            [
                'id' => $resource->id,
                'learning_resource_type_id' => $type->id,
                'title' => 'Science 6 Textbook',
                'publisher' => 'DepEd Press',
                'quantity_delivered' => 10,
                'quantity_with_issue_defect' => 0,
                'remarks' => null,
            ],
        ],
    ])->assertUnprocessable();

    expect($resource->fresh()->quantity_delivered)->toBe(30);
});

test('a school cannot record movements for another schools resource', function () {
    [, , $type] = createSchoolUserWithType();

    $otherSchool = School::factory()->create();
    $otherResource = LearningResource::factory()->create([
        'school_id' => $otherSchool->id,
        'learning_resource_type_id' => $type->id,
    ]);

    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);
    $school = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'is_activated' => true,
    ]);
    $user = User::factory()->schoolUser($school)->create();

    $this->actingAs($user)
        ->post(route('school.inventory.movements.store', $otherResource), [
            'type' => 'issued',
            'quantity' => 1,
        ])
        ->assertForbidden();
});

test('school user can view the inventory page', function () {
    [, $user, $type] = createSchoolUserWithType();

    encodeResource($user, $type);

    $this->actingAs($user)
        ->get(route('school.inventory.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolInventory')
            ->has('resources', 1)
            ->where('resources.0.inventory.available', 28)
            ->has('movements', 2)
        );
});

test('removing an encoded resource removes its inventory and movements', function () {
    [$school, $user, $type] = createSchoolUserWithType();

    $resource = encodeResource($user, $type);

    $this->actingAs($user)->putJson(route('school.resources.store'), [
        'resources' => [
            [
                'learning_resource_type_id' => $type->id,
                'title' => 'Replacement Module',
                'publisher' => 'DepEd Press',
                'quantity_delivered' => 5,
                'quantity_with_issue_defect' => 0,
                'remarks' => null,
            ],
        ],
    ])->assertOk();

    expect(LearningResource::whereKey($resource->id)->exists())->toBeFalse();
    expect(InventoryMovement::where('learning_resource_id', $resource->id)->count())->toBe(0);
    expect(LearningResource::where('school_id', $school->id)->count())->toBe(1);
});
