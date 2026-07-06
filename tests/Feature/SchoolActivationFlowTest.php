<?php

use App\Models\District;
use App\Models\Municipality;
use App\Models\School;
use App\Models\User;

test('school can activate account and receive generated credentials once', function () {
    $district = District::factory()->create();
    $municipality = Municipality::factory()->create(['district_id' => $district->id]);

    $school = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'school_id' => 'SID-99999',
        'is_activated' => false,
    ]);

    $lookupResponse = $this->postJson(route('school.find'), [
        'school_id' => 'SID-99999',
    ]);

    $lookupResponse
        ->assertOk()
        ->assertJsonPath('next_url', route('school.activate.edit', $school));

    $activationResponse = $this->post(route('school.activate.store', $school), [
        'school_head' => 'Maria Dela Cruz',
        'librarian' => 'Ana Santos',
        'property_custodian' => 'Pedro Reyes',
        'email' => 'school@example.com',
    ]);

    $activationResponse->assertRedirect(route('school.activate.credentials', $school));

    $school->refresh();

    expect($school->is_activated)->toBeTrue();
    expect($school->email)->toBe('school@example.com');

    $user = User::where('email', 'school@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->role)->toBe('school');
    expect($user->school_id)->toBe($school->id);

    $credentialsPage = $this->get(route('school.activate.credentials', $school));
    $credentialsPage->assertOk();

    $credentialsPageSecondTry = $this->get(route('school.activate.credentials', $school));
    $credentialsPageSecondTry->assertRedirect(route('login'));
});

test('activated school id lookup redirects to update page and allows details update', function () {
    $district = District::factory()->create();
    $municipality = Municipality::factory()->create(['district_id' => $district->id]);

    $school = School::factory()->create([
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'school_id' => 'SID-77777',
        'is_activated' => true,
    ]);

    $user = User::factory()->schoolUser($school)->create([
        'email' => 'old-email@example.com',
    ]);

    $school->update([
        'user_id' => $user->id,
        'email' => $user->email,
        'school_head' => null,
    ]);

    $lookupResponse = $this->postJson(route('school.find'), [
        'school_id' => 'SID-77777',
    ]);

    $lookupResponse
        ->assertOk()
        ->assertJsonPath('next_url', route('school.activate.edit', $school));

    $response = $this
        ->actingAs($user)
        ->post(route('school.activate.store', $school), [
            'school_head' => 'Updated Head',
            'librarian' => 'Updated Librarian',
            'property_custodian' => 'Updated Custodian',
            'email' => 'updated-school@example.com',
        ]);

    $response->assertRedirect(route('dashboard'));

    $school->refresh();
    $user->refresh();

    expect($school->school_head)->toBe('Updated Head');
    expect($school->email)->toBe('updated-school@example.com');
    expect($user->email)->toBe('updated-school@example.com');
});
