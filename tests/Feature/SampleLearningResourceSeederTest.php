<?php

use App\Models\District;
use App\Models\LearningResource;
use App\Models\Municipality;
use App\Models\School;
use Database\Seeders\SampleLearningResourceSeeder;

test('the sample seeder catalogues the print resource with its inventory', function () {
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
    ]);

    $this->seed(SampleLearningResourceSeeder::class);

    $resource = LearningResource::where('isbn', '978-971-94761-9-6')->sole();

    expect($resource->title)->toBe('Edukasyon Sa Pagpapahalaga: Mga Pagpapahalaga Tungo Sa Pagtupad Ng Tungkulin');
    expect($resource->author)->toBe('Vanessa M. Espiritu');
    expect($resource->publisher)->toBe('Acfa Enterprises');
    expect($resource->language)->toBe('Tagalog');
    expect($resource->subject)->toBe('Values Education');
    expect($resource->copyright_year)->toBe(2024);
    expect($resource->pages)->toBe(194);
    expect($resource->learningResourceType->name)->toBe("Teacher's Manuals (TM)");
    expect($resource->gradeLevel->name)->toBe('Grade 7');
    expect($resource->attachment_path)->not->toBeNull();
    expect($resource->cover_image_path)->not->toBeNull();
    expect($resource->inventory->available)->toBe(10);
});

test('running the sample seeder twice does not duplicate the resource', function () {
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
    ]);

    $this->seed(SampleLearningResourceSeeder::class);
    $this->seed(SampleLearningResourceSeeder::class);

    expect(LearningResource::where('isbn', '978-971-94761-9-6')->count())->toBe(1);
});

test('the sample seeder skips gracefully when no school exists', function () {
    $this->seed(SampleLearningResourceSeeder::class);

    expect(LearningResource::count())->toBe(0);
});
