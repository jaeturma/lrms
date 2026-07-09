<?php

use App\Models\DigitalLearningMaterial;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

test('catalog roles can view and manage digital learning materials', function () {
    $supply = User::factory()->create(['role' => 'supply']);

    $this->actingAs($supply)
        ->get(route('admin.digital-learning-materials.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminDigitalLearningMaterials')
            ->where('canManage', true)
        );

    $this->actingAs($supply)
        ->post(route('admin.digital-learning-materials.store'), [
            'name' => 'Grade 5 Math H5P Module',
            'category' => 'Learning Material',
            'type' => 'H5P (HTML5 Package)',
            'publisher' => 'DepEd Learning Resources',
            'link' => 'https://commons.deped.gov.ph/example',
            'quality_assured' => true,
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $material = DigitalLearningMaterial::where('name', 'Grade 5 Math H5P Module')->sole();

    expect($material->category)->toBe('Learning Material');
    expect($material->type)->toBe('H5P (HTML5 Package)');
    expect($material->quality_assured)->toBeTrue();

    $this->actingAs($supply)
        ->put(route('admin.digital-learning-materials.update', $material), [
            'name' => 'Grade 5 Math H5P Module',
            'category' => 'Learning Material',
            'type' => 'H5P (HTML5 Package)',
            'is_active' => false,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($material->fresh()->is_active)->toBeFalse();

    $this->actingAs($supply)
        ->delete(route('admin.digital-learning-materials.destroy', $material))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('digital_learning_materials', ['id' => $material->id]);
});

test('executive roles can view but not manage digital learning materials', function () {
    $cidChief = User::factory()->create(['role' => 'cidchief']);

    $this->actingAs($cidChief)
        ->get(route('admin.digital-learning-materials.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminDigitalLearningMaterials')
            ->where('canManage', false)
        );

    $this->actingAs($cidChief)
        ->post(route('admin.digital-learning-materials.store'), [
            'name' => 'Should Not Be Created',
            'category' => 'Learning Material',
            'type' => 'PDF',
        ])
        ->assertForbidden();

    $material = DigitalLearningMaterial::factory()->create();

    $this->actingAs($cidChief)
        ->put(route('admin.digital-learning-materials.update', $material), [
            'name' => $material->name,
            'category' => $material->category,
            'type' => $material->type,
        ])
        ->assertForbidden();

    $this->actingAs($cidChief)
        ->delete(route('admin.digital-learning-materials.destroy', $material))
        ->assertForbidden();
});

test('school users cannot access digital learning materials at all', function () {
    $schoolUser = User::factory()->schoolUser()->create();

    $this->actingAs($schoolUser)
        ->get(route('admin.digital-learning-materials.index'))
        ->assertForbidden();
});

test('digital learning material category and type must be valid', function () {
    $supply = User::factory()->create(['role' => 'supply']);

    $this->actingAs($supply)
        ->post(route('admin.digital-learning-materials.store'), [
            'name' => 'Invalid Entry',
            'category' => 'Not A Real Category',
            'type' => 'Not A Real Type',
        ])
        ->assertSessionHasErrors(['category', 'type']);
});

test('admin can filter digital learning materials by category, type, and quality assured', function () {
    $admin = User::factory()->admin()->create();

    DigitalLearningMaterial::factory()->create([
        'name' => 'Quality Assured Video',
        'category' => 'Learning Material',
        'type' => 'Video',
        'quality_assured' => true,
    ]);
    DigitalLearningMaterial::factory()->create([
        'name' => 'Draft Lesson Plan',
        'category' => 'Lesson Plan',
        'type' => 'Word Document',
        'quality_assured' => false,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.digital-learning-materials.index', ['category' => 'Learning Material', 'type' => 'Video', 'quality_assured' => '1']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminDigitalLearningMaterials')
            ->has('materials.data', 1)
            ->where('materials.data.0.name', 'Quality Assured Video')
        );
});

test('admin can import digital learning materials via csv', function () {
    $admin = User::factory()->admin()->create();

    $csv = UploadedFile::fake()->createWithContent('digital-lm.csv', implode("\n", [
        'name,category,type,publisher,link,description,quality_assured,is_active',
        'Imported H5P Package,Learning Material,H5P (HTML5 Package),DepEd,https://example.com,Sample import,1,1',
    ]));

    $this->actingAs($admin)
        ->postJson(route('admin.digital-learning-materials.import.store'), ['file' => $csv])
        ->assertOk()
        ->assertJsonPath('summary.imported', 1);

    $this->assertDatabaseHas('digital_learning_materials', [
        'name' => 'Imported H5P Package',
        'type' => 'H5P (HTML5 Package)',
        'quality_assured' => true,
    ]);
});

test('admin can download the digital learning materials import template', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.digital-learning-materials.import.template'))
        ->assertOk()
        ->assertDownload('digital-learning-materials-import-template.csv');
});

test('school user can browse active digital learning materials', function () {
    $schoolUser = User::factory()->schoolUser()->create();

    DigitalLearningMaterial::factory()->create(['name' => 'Visible Material', 'is_active' => true]);
    DigitalLearningMaterial::factory()->create(['name' => 'Hidden Material', 'is_active' => false]);

    $this->actingAs($schoolUser)
        ->get(route('school.digital-learning-materials.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolDigitalLearningMaterials')
            ->has('materials.data', 1)
            ->where('materials.data.0.name', 'Visible Material')
            ->has('categories')
            ->has('types')
        );
});

test('school user can filter digital learning materials by category and type', function () {
    $schoolUser = User::factory()->schoolUser()->create();

    DigitalLearningMaterial::factory()->create([
        'name' => 'Grade 5 Video Lesson',
        'category' => 'Learning Material',
        'type' => 'Video',
        'is_active' => true,
    ]);
    DigitalLearningMaterial::factory()->create([
        'name' => 'Weekly Lesson Plan',
        'category' => 'Lesson Plan',
        'type' => 'Word Document',
        'is_active' => true,
    ]);

    $this->actingAs($schoolUser)
        ->get(route('school.digital-learning-materials.index', ['category' => 'Learning Material', 'type' => 'Video']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolDigitalLearningMaterials')
            ->has('materials.data', 1)
            ->where('materials.data.0.name', 'Grade 5 Video Lesson')
        );
});

test('non school roles cannot access the school digital learning materials page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('school.digital-learning-materials.index'))
        ->assertForbidden();
});
