<?php

use App\Models\District;
use App\Models\LearningResource;
use App\Models\LearningResourceType;
use App\Models\Municipality;
use App\Models\School;
use App\Models\User;

test('school user can save dynamic learning resources', function () {
    $bookType = LearningResourceType::query()->create(['name' => 'Book', 'is_active' => true]);
    $moduleType = LearningResourceType::query()->create(['name' => 'Module', 'is_active' => true]);

    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

    $school = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'is_activated' => true,
    ]);

    $user = User::factory()->schoolUser($school)->create([
        'email' => 'school-user@example.com',
    ]);

    $school->update([
        'user_id' => $user->id,
        'email' => $user->email,
    ]);

    $payload = [
        'resources' => [
            [
                'learning_resource_type_id' => $bookType->id,
                'title' => 'Science Grade 6 Textbook',
                'publisher' => 'DepEd Press',
                'quantity_delivered' => 5,
                'quantity_with_issue_defect' => 1,
                'remarks' => 'Missing pages',
            ],
            [
                'learning_resource_type_id' => $moduleType->id,
                'title' => 'Math Quarter 1 Module',
                'publisher' => 'School Publisher',
                'quantity_delivered' => 2,
                'quantity_with_issue_defect' => 1,
                'remarks' => 'Unreadable print',
            ],
        ],
    ];

    $response = $this
        ->actingAs($user)
        ->putJson(route('school.resources.store'), $payload);

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Learning resources saved successfully.');

    expect(LearningResource::where('school_id', $school->id)->count())->toBe(2);
});
