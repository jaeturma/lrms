<?php

use App\Models\District;
use App\Models\LearningResource;
use App\Models\LearningResourceType;
use App\Models\Municipality;
use App\Models\ResourceDistribution;
use App\Models\ResourceTitle;
use App\Models\School;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function createDistributionSchoolUser(): array
{
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    $school = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'is_activated' => true,
    ]);

    $user = User::factory()->schoolUser($school)->create();
    $school->update(['user_id' => $user->id]);

    return [$school, $user];
}

test('admin can record a delivery with a generated reference code', function () {
    $admin = User::factory()->admin()->create();
    [$school] = createDistributionSchoolUser();
    $type = LearningResourceType::factory()->create(['is_active' => true]);
    $title = ResourceTitle::factory()->create([
        'learning_resource_type_id' => $type->id,
        'title' => 'Grade 3 Mathematics Textbook',
        'author' => 'DepEd',
        'publisher' => 'DepEd Central Office',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.distributions.store'), [
            'school_id' => $school->id,
            'resource_title_id' => $title->id,
            'quantity' => 120,
            'notes' => 'First tranche',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $distribution = ResourceDistribution::sole();

    expect($distribution->status)->toBe('pending');
    expect($distribution->reference_code)->toStartWith('DST-');
    expect($distribution->created_by)->toBe($admin->id);
    expect($distribution->resource_title_id)->toBe($title->id);
    expect($distribution->learning_resource_type_id)->toBe($type->id);
    expect($distribution->title)->toBe('Grade 3 Mathematics Textbook');
    expect($distribution->quantity)->toBe(120);
});

test('admin cannot record a delivery from an inactive catalog title', function () {
    $admin = User::factory()->admin()->create();
    [$school] = createDistributionSchoolUser();
    $title = ResourceTitle::factory()->create(['is_active' => false]);

    $this->actingAs($admin)
        ->post(route('admin.distributions.store'), [
            'school_id' => $school->id,
            'resource_title_id' => $title->id,
            'quantity' => 20,
        ])
        ->assertSessionHasErrors('resource_title_id');

    expect(ResourceDistribution::count())->toBe(0);
});

test('admin can cancel a pending delivery but not a received one', function () {
    $admin = User::factory()->admin()->create();

    $pending = ResourceDistribution::factory()->create(['status' => 'pending']);
    $received = ResourceDistribution::factory()->create(['status' => 'received']);

    $this->actingAs($admin)
        ->post(route('admin.distributions.cancel', $pending))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($pending->refresh()->status)->toBe('cancelled');

    $this->actingAs($admin)
        ->post(route('admin.distributions.cancel', $received))
        ->assertSessionHasErrors('status');

    expect($received->refresh()->status)->toBe('received');
});

test('school user sees only their own deliveries', function () {
    [$school, $user] = createDistributionSchoolUser();

    ResourceDistribution::factory()->count(2)->create(['school_id' => $school->id]);
    ResourceDistribution::factory()->create();

    $this->actingAs($user)
        ->get(route('school.distributions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolDistributions')
            ->has('distributions', 2)
        );
});

test('confirming receipt creates the learning resource and inventory', function () {
    [$school, $user] = createDistributionSchoolUser();
    $title = ResourceTitle::factory()->create([
        'title' => 'Science Activity Workbook',
        'author' => 'Maria Santos',
        'publisher' => 'DepEd Central',
    ]);

    $distribution = ResourceDistribution::factory()->create([
        'school_id' => $school->id,
        'learning_resource_type_id' => $title->learning_resource_type_id,
        'resource_title_id' => $title->id,
        'title' => $title->title,
        'publisher' => $title->publisher,
        'quantity' => 50,
    ]);

    $this->actingAs($user)
        ->post(route('school.distributions.receive', $distribution), [
            'quantity_damaged' => 5,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $distribution->refresh();

    expect($distribution->status)->toBe('received');
    expect($distribution->received_by)->toBe($user->id);
    expect($distribution->quantity_damaged)->toBe(5);
    expect($distribution->received_at)->not->toBeNull();

    $resource = LearningResource::where('school_id', $school->id)->sole();

    expect($distribution->learning_resource_id)->toBe($resource->id);
    expect($resource->resource_title_id)->toBe($title->id);
    expect($resource->title)->toBe('Science Activity Workbook');
    expect($resource->author)->toBe('Maria Santos');
    expect($resource->quantity_delivered)->toBe(50);

    $inventory = $resource->inventory;

    expect($inventory->available)->toBe(45);
    expect($inventory->damaged)->toBe(5);

    $movementTypes = $resource->inventoryMovements()->pluck('type');

    expect($movementTypes)->toContain('received');
    expect($movementTypes)->toContain('damaged');
});

test('a delivery cannot be received twice', function () {
    [$school, $user] = createDistributionSchoolUser();

    $distribution = ResourceDistribution::factory()->create(['school_id' => $school->id]);

    $this->actingAs($user)
        ->post(route('school.distributions.receive', $distribution))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->post(route('school.distributions.receive', $distribution))
        ->assertSessionHasErrors('status');

    expect(LearningResource::where('school_id', $school->id)->count())->toBe(1);
});

test('damaged quantity cannot exceed the delivered quantity', function () {
    [$school, $user] = createDistributionSchoolUser();

    $distribution = ResourceDistribution::factory()->create([
        'school_id' => $school->id,
        'quantity' => 10,
    ]);

    $this->actingAs($user)
        ->post(route('school.distributions.receive', $distribution), [
            'quantity_damaged' => 11,
        ])
        ->assertSessionHasErrors('quantity_damaged');

    expect($distribution->refresh()->status)->toBe('pending');
});

test('a school cannot receive another schools delivery', function () {
    [, $user] = createDistributionSchoolUser();

    $otherDistribution = ResourceDistribution::factory()->create();

    $this->actingAs($user)
        ->post(route('school.distributions.receive', $otherDistribution))
        ->assertForbidden();

    expect($otherDistribution->refresh()->status)->toBe('pending');
});

test('school users cannot access the admin distributions page', function () {
    [, $user] = createDistributionSchoolUser();

    $this->actingAs($user)
        ->get(route('admin.distributions.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('admin.distributions.store'), [])
        ->assertForbidden();
});

test('admin distributions page renders with filters and summary', function () {
    $admin = User::factory()->admin()->create();

    ResourceDistribution::factory()->count(2)->create(['status' => 'pending']);
    ResourceDistribution::factory()->create(['status' => 'received']);
    ResourceTitle::factory()->count(2)->create(['is_active' => true]);

    $this->actingAs($admin)
        ->get(route('admin.distributions.index', ['status' => 'pending']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminDistributions')
            ->has('distributions.data', 2)
            ->where('summary.pending', 2)
            ->where('summary.received', 1)
            ->has('schools')
            ->has('resourceTitles', 2)
        );
});
