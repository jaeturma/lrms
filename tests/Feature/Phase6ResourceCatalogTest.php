<?php

use App\Models\District;
use App\Models\LearningResource;
use App\Models\LearningResourceType;
use App\Models\Municipality;
use App\Models\ResourceTitle;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function createCatalogSchoolUser(): array
{
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    $school = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'is_activated' => true,
        'school_head' => 'Test Head',
        'email' => 'catalog-school@example.com',
    ]);

    $user = User::factory()->schoolUser($school)->create();
    $school->update(['user_id' => $user->id]);

    return [$school, $user];
}

test('admin can add a catalog title with cover image and attachment', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $type = LearningResourceType::factory()->create(['is_active' => true]);

    $this->actingAs($admin)
        ->post(route('admin.resource-titles.store'), [
            'learning_resource_type_id' => $type->id,
            'title' => 'Edukasyon Sa Pagpapahalaga: Mga Pagpapahalaga Tungo Sa Pagtupad Ng Tungkulin',
            'author' => 'Vanessa M. Espiritu',
            'publisher' => 'Acfa Enterprises',
            'language' => 'Tagalog',
            'subject' => 'Values Education',
            'copyright_year' => 2024,
            'pages' => 194,
            'isbn' => '978-971-94761-9-6',
            'media_url' => 'https://example.com/animations/esp7.mp4',
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 400, 600),
            'attachment' => UploadedFile::fake()->create('manual.pdf', 512, 'application/pdf'),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $title = ResourceTitle::sole();

    expect($title->author)->toBe('Vanessa M. Espiritu');
    expect($title->isbn)->toBe('978-971-94761-9-6');
    expect($title->is_active)->toBeTrue();
    expect($title->cover_image_path)->not->toBeNull();
    expect($title->attachment_path)->not->toBeNull();

    Storage::disk('public')->assertExists($title->cover_image_path);
    Storage::disk('public')->assertExists($title->attachment_path);
});

test('admin can update a title and replace its cover image', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $type = LearningResourceType::factory()->create(['is_active' => true]);

    $oldCover = UploadedFile::fake()->image('old.jpg')->store('resource-titles/covers', 'public');
    $title = ResourceTitle::factory()->create([
        'learning_resource_type_id' => $type->id,
        'cover_image_path' => $oldCover,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.resource-titles.update', $title), [
            'learning_resource_type_id' => $type->id,
            'title' => 'Updated Catalog Title',
            'cover_image' => UploadedFile::fake()->image('new.jpg'),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $title->refresh();

    expect($title->title)->toBe('Updated Catalog Title');
    Storage::disk('public')->assertMissing($oldCover);
    Storage::disk('public')->assertExists($title->cover_image_path);
});

test('a title used by schools cannot be deleted but an unused one can', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    [$school] = createCatalogSchoolUser();

    $usedTitle = ResourceTitle::factory()->create();
    LearningResource::factory()->create([
        'school_id' => $school->id,
        'resource_title_id' => $usedTitle->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.resource-titles.destroy', $usedTitle))
        ->assertSessionHasErrors('resource_title');

    expect(ResourceTitle::whereKey($usedTitle->id)->exists())->toBeTrue();

    $cover = UploadedFile::fake()->image('cover.jpg')->store('resource-titles/covers', 'public');
    $unusedTitle = ResourceTitle::factory()->create(['cover_image_path' => $cover]);

    $this->actingAs($admin)
        ->delete(route('admin.resource-titles.destroy', $unusedTitle))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(ResourceTitle::whereKey($unusedTitle->id)->exists())->toBeFalse();
    Storage::disk('public')->assertMissing($cover);
});

test('school encodes a catalog title by entering quantities only', function () {
    [$school, $user] = createCatalogSchoolUser();

    $title = ResourceTitle::factory()->create([
        'title' => 'Catalog Science Module',
        'author' => 'Juan Dela Cruz',
        'publisher' => 'DepEd Central',
        'language' => 'English',
        'subject' => 'Science',
        'isbn' => '978-000-00000-1-1',
    ]);

    $this->actingAs($user)
        ->put(route('school.resources.store'), [
            'resources' => [
                [
                    'resource_title_id' => $title->id,
                    'quantity_delivered' => 40,
                    'quantity_with_issue_defect' => 4,
                ],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $resource = LearningResource::where('school_id', $school->id)->sole();

    expect($resource->resource_title_id)->toBe($title->id);
    expect($resource->title)->toBe('Catalog Science Module');
    expect($resource->author)->toBe('Juan Dela Cruz');
    expect($resource->publisher)->toBe('DepEd Central');
    expect($resource->subject)->toBe('Science');
    expect($resource->isbn)->toBe('978-000-00000-1-1');
    expect($resource->learning_resource_type_id)->toBe($title->learning_resource_type_id);

    expect($resource->inventory->available)->toBe(36);
    expect($resource->inventory->damaged)->toBe(4);
});

test('an inactive catalog title cannot be encoded', function () {
    [, $user] = createCatalogSchoolUser();

    $title = ResourceTitle::factory()->create(['is_active' => false]);

    $this->actingAs($user)
        ->put(route('school.resources.store'), [
            'resources' => [
                [
                    'resource_title_id' => $title->id,
                    'quantity_delivered' => 10,
                    'quantity_with_issue_defect' => 0,
                ],
            ],
        ])
        ->assertSessionHasErrors('resources.0.resource_title_id');
});

test('manual encoding without a catalog title still works', function () {
    [$school, $user] = createCatalogSchoolUser();

    $type = LearningResourceType::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->put(route('school.resources.store'), [
            'resources' => [
                [
                    'learning_resource_type_id' => $type->id,
                    'title' => 'Locally Produced Module',
                    'publisher' => 'School Press',
                    'quantity_delivered' => 15,
                    'quantity_with_issue_defect' => 0,
                ],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $resource = LearningResource::where('school_id', $school->id)->sole();

    expect($resource->resource_title_id)->toBeNull();
    expect($resource->title)->toBe('Locally Produced Module');
});

test('the catalog page renders for admins and is forbidden for schools', function () {
    $admin = User::factory()->admin()->create();
    [, $user] = createCatalogSchoolUser();

    ResourceTitle::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get(route('admin.resource-titles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminResourceTitles')
            ->has('resourceTitles.data', 2)
            ->has('resourceTypes')
            ->has('gradeLevels')
        );

    $this->actingAs($user)->get(route('admin.resource-titles.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.resource-titles.store'), [])->assertForbidden();
});
