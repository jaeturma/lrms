<?php

use App\Models\School;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $school = School::factory()->create([
        'is_activated' => true,
        'school_head' => 'Maria Head',
    ]);
    $user = User::factory()->schoolUser($school)->create();
    $school->update(['user_id' => $user->id, 'email' => $user->email]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('school user without updated details is redirected to school update page', function () {
    $school = School::factory()->create([
        'is_activated' => true,
        'school_head' => null,
    ]);
    $user = User::factory()->schoolUser($school)->create();
    $school->update(['user_id' => $user->id, 'email' => $user->email]);

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertRedirect(route('school.activate.edit', $school));
});
