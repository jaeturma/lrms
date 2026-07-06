<?php

use App\Models\District;
use App\Models\LearningResource;
use App\Models\LearningResourceType;
use App\Models\Municipality;
use App\Models\School;
use App\Models\User;

test('school user can save dynamic learning resources', function () {
    LearningResourceType::query()->create(['name' => 'Book', 'is_active' => true]);
    LearningResourceType::query()->create(['name' => 'Module', 'is_active' => true]);

    $district = District::factory()->create();
    $municipality = Municipality::factory()->create(['district_id' => $district->id]);

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
                'resource_type' => 'Book',
                'issue_defect' => 'Missing pages',
                'quantity' => 5,
                'publisher' => 'DepEd Press',
            ],
            [
                'resource_type' => 'Module',
                'issue_defect' => 'Unreadable print',
                'quantity' => 2,
                'publisher' => 'School Publisher',
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
