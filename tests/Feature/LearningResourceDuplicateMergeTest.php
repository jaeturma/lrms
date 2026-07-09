<?php

use App\Models\District;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\InventoryMovement;
use App\Models\LearningResource;
use App\Models\Municipality;
use App\Models\ResourceDistribution;
use App\Models\ResourceTitle;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\User;
use App\Services\LearningResourceDuplicateMerger;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

function createMergeTestSchool(array $attributes = []): School
{
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    return School::factory()->create(array_merge([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
    ], $attributes));
}

/**
 * The migration merges pre-existing duplicates before adding the unique
 * index, so on a fresh (duplicate-free) test database the index is already
 * in place. Drop it so tests can simulate legacy duplicate rows left over
 * from before the receive() fix. Runs inside RefreshDatabase's per-test
 * transaction, so SQLite rolls the schema change back after each test.
 */
function allowDuplicateLearningResourceRows(): void
{
    try {
        Schema::table('learning_resources', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'resource_title_id']);
        });
    } catch (Throwable) {
        // Already dropped earlier in this test.
    }
}

/**
 * Create a duplicate LearningResource row directly (bypassing the now-fixed
 * receive() flow), simulating data left over from before the fix.
 */
function createDuplicateResource(School $school, ResourceTitle $title, int $available, int $damaged = 0): LearningResource
{
    allowDuplicateLearningResourceRows();

    $resource = LearningResource::factory()->create([
        'school_id' => $school->id,
        'resource_title_id' => $title->id,
        'learning_resource_type_id' => $title->learning_resource_type_id,
        'title' => $title->title,
        'publisher' => $title->publisher,
        'quantity_delivered' => $available + $damaged,
        'quantity_with_issue_defect' => $damaged,
    ]);

    $resource->inventory()->create(['available' => $available, 'damaged' => $damaged]);
    $resource->inventoryMovements()->create([
        'school_id' => $school->id,
        'type' => 'received',
        'quantity' => $available + $damaged,
        'to_status' => 'available',
        'notes' => 'Seeded for test',
    ]);

    return $resource;
}

test('find-duplicates detects rows sharing the same school and catalog title', function () {
    $school = createMergeTestSchool();
    $title = ResourceTitle::factory()->create();

    createDuplicateResource($school, $title, 10);
    createDuplicateResource($school, $title, 5);

    $groups = LearningResource::duplicateGroups();

    expect($groups)->toHaveCount(1);
    expect($groups->first()['school_id'])->toBe($school->id);
    expect($groups->first()['resource_title_id'])->toBe($title->id);
    expect($groups->first()['total'])->toBe(2);
});

test('find-duplicates ignores manual entries with no catalog title', function () {
    $school = createMergeTestSchool();

    LearningResource::factory()->count(3)->create(['school_id' => $school->id, 'resource_title_id' => null]);

    expect(LearningResource::duplicateGroups())->toBeEmpty();
});

test('merging combines quantities and inventory onto the oldest row', function () {
    $school = createMergeTestSchool();
    $title = ResourceTitle::factory()->create();

    $survivor = createDuplicateResource($school, $title, available: 20, damaged: 2);
    $survivor->update(['created_at' => now()->subDays(2)]);

    $duplicate = createDuplicateResource($school, $title, available: 8, damaged: 1);

    app(LearningResourceDuplicateMerger::class)->mergeAll();

    expect(LearningResource::where('school_id', $school->id)->count())->toBe(1);

    $survivor->refresh();

    expect($survivor->quantity_delivered)->toBe(31); // 22 + 9
    expect($survivor->quantity_with_issue_defect)->toBe(3);
    expect($survivor->inventory->available)->toBe(28);
    expect($survivor->inventory->damaged)->toBe(3);

    // The duplicate is soft-deleted, not destroyed, and its own ledger is
    // zeroed so it can never be double-counted if queried directly.
    $rawDuplicate = LearningResource::withTrashed()->find($duplicate->id);

    expect($rawDuplicate->trashed())->toBeTrue();
    expect($rawDuplicate->resource_title_id)->toBeNull();
    expect($rawDuplicate->quantity_delivered)->toBe(0);
    expect($rawDuplicate->inventory->available)->toBe(0);
    expect($rawDuplicate->inventory->damaged)->toBe(0);
});

test('merging re-points movement history and distribution links instead of deleting them', function () {
    $school = createMergeTestSchool();
    $title = ResourceTitle::factory()->create();

    $survivor = createDuplicateResource($school, $title, available: 20);
    $survivor->update(['created_at' => now()->subDay()]);
    $duplicate = createDuplicateResource($school, $title, available: 10);

    $distribution = ResourceDistribution::factory()->create([
        'school_id' => $school->id,
        'resource_title_id' => $title->id,
        'status' => 'received',
        'learning_resource_id' => $duplicate->id,
    ]);

    $movementCountBefore = InventoryMovement::where('learning_resource_id', $duplicate->id)->count();
    expect($movementCountBefore)->toBeGreaterThan(0);

    app(LearningResourceDuplicateMerger::class)->mergeAll();

    // No movement rows were deleted — they were re-pointed to the survivor.
    expect(InventoryMovement::count())->toBe($movementCountBefore + 1 /* survivor's own seed */ + 1 /* merge adjustment */);
    expect(InventoryMovement::where('learning_resource_id', $duplicate->id)->count())->toBe(0);
    expect(InventoryMovement::where('learning_resource_id', $survivor->id)->count())->toBe($movementCountBefore + 2);

    $distribution->refresh();
    expect($distribution->learning_resource_id)->toBe($survivor->id);
});

test('merging is idempotent', function () {
    $school = createMergeTestSchool();
    $title = ResourceTitle::factory()->create();

    createDuplicateResource($school, $title, 10);
    createDuplicateResource($school, $title, 5);

    $merger = app(LearningResourceDuplicateMerger::class);

    expect($merger->mergeAll())->toBe(1);
    expect($merger->mergeAll())->toBe(0); // nothing left to merge
    expect(LearningResource::where('school_id', $school->id)->count())->toBe(1);
});

test('merge-duplicates command with --dry-run makes no changes', function () {
    $school = createMergeTestSchool();
    $title = ResourceTitle::factory()->create();

    createDuplicateResource($school, $title, 10);
    createDuplicateResource($school, $title, 5);

    Artisan::call('learning-resources:merge-duplicates', ['--dry-run' => true]);

    expect(LearningResource::where('school_id', $school->id)->count())->toBe(2);
});

test('merge-duplicates command without --dry-run merges', function () {
    $school = createMergeTestSchool();
    $title = ResourceTitle::factory()->create();

    createDuplicateResource($school, $title, 10);
    createDuplicateResource($school, $title, 5);

    Artisan::call('learning-resources:merge-duplicates');

    expect(LearningResource::where('school_id', $school->id)->count())->toBe(1);
});

test('the unique index prevents a duplicate row from being inserted directly', function () {
    $school = createMergeTestSchool();
    $title = ResourceTitle::factory()->create();

    LearningResource::factory()->create(['school_id' => $school->id, 'resource_title_id' => $title->id]);

    expect(fn () => LearningResource::factory()->create(['school_id' => $school->id, 'resource_title_id' => $title->id]))
        ->toThrow(QueryException::class);
});

test('reports still aggregate available copies correctly after a merge', function () {
    $admin = User::factory()->admin()->create();
    $schoolYear = SchoolYear::factory()->active()->create();
    $school = createMergeTestSchool(['school_name' => 'Merge Report School']);
    $title = ResourceTitle::factory()->create();

    Enrollment::factory()->create([
        'school_id' => $school->id,
        'school_year_id' => $schoolYear->id,
        'grade_level_id' => GradeLevel::factory()->create()->id,
        'male_count' => 10,
        'female_count' => 10,
    ]);

    createDuplicateResource($school, $title, available: 12);
    createDuplicateResource($school, $title, available: 8);

    // Aggregate is correct even before merging (School::learningResourceInventories()
    // already sums across every row) — confirms the merge is a UX/ledger fix,
    // not a reporting-correctness fix.
    $this->actingAs($admin)
        ->get(route('admin.reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('resourceAdequacy.0.available_copies', 20)
        );

    app(LearningResourceDuplicateMerger::class)->mergeAll();

    // And the aggregate is unchanged after merging — no copies were lost or
    // double-counted in the process.
    $this->actingAs($admin)
        ->get(route('admin.reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('resourceAdequacy.0.available_copies', 20)
        );
});
