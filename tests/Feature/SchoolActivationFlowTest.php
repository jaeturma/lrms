<?php

use App\Mail\SchoolActivationCredentialsMail;
use App\Mail\SchoolActivationOtpMail;
use App\Models\AppSetting;
use App\Models\Barangay;
use App\Models\District;
use App\Models\Municipality;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

test('school can activate account via otp and receive generated credentials once', function () {
    $municipality = Municipality::factory()->create(['name' => 'Maco']);
    $district = District::factory()->create(['municipality_id' => $municipality->id, 'name' => 'District 1']);
    $barangay = Barangay::factory()->create(['municipality_id' => $municipality->id, 'name' => 'Lapu-lapu']);

    $school = School::factory()->create([
        'district_id' => null,
        'municipality_id' => null,
        'school_id' => 'SID-99999',
        'is_activated' => false,
    ]);

    Mail::fake();

    AppSetting::query()->updateOrCreate(['key' => 'smtp_enabled'], ['value' => '1']);
    AppSetting::query()->updateOrCreate(['key' => 'smtp_host'], ['value' => 'smtp.example.test']);
    AppSetting::query()->updateOrCreate(['key' => 'smtp_port'], ['value' => '587']);

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
        'municipality_id' => $municipality->id,
        'district_id' => $district->id,
        'barangay_id' => $barangay->id,
        'primary_mobile_no' => '09171234567',
        'secondary_mobile_no' => '09281234567',
    ]);

    $activationResponse->assertRedirect(route('school.activate.edit', $school));

    $otp = null;
    Mail::assertSent(SchoolActivationOtpMail::class, function (SchoolActivationOtpMail $mail) use (&$otp): bool {
        $otp = $mail->otp;

        return true;
    });

    expect($otp)->not->toBeNull();

    $verifyResponse = $this->post(route('school.activate.verify-otp', $school), [
        'otp' => $otp,
    ]);

    $verifyResponse->assertRedirect(route('school.activate.credentials', $school));

    Mail::assertSent(SchoolActivationCredentialsMail::class, function (SchoolActivationCredentialsMail $mail): bool {
        return $mail->email === 'school@example.com';
    });

    $school->refresh();

    expect($school->is_activated)->toBeTrue();
    expect($school->email)->toBe('school@example.com');
    expect($school->municipality?->name)->toBe('Maco');
    expect($school->district?->name)->toBe('District 1');
    expect($school->barangay?->name)->toBe('Lapu-lapu');
    expect($school->primary_mobile_no)->toBe('09171234567');
    expect($school->secondary_mobile_no)->toBe('09281234567');

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
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);

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
        ->assertJsonPath('is_activated', true)
        ->assertJsonPath('redirect_url', route('login'));

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

test('school id lookup rejects values longer than ten characters', function () {
    School::factory()->create([
        'school_id' => 'SID-12345',
    ]);

    $response = $this->postJson(route('school.find'), [
        'school_id' => 'SID-1234567',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('school_id');
});

test('school activation rejects entries beyond configured character limits', function () {
    $school = School::factory()->create([
        'school_id' => 'SID-45678',
        'is_activated' => false,
    ]);

    $response = $this->post(route('school.activate.store', $school), [
        'school_head' => str_repeat('A', 81),
        'librarian' => str_repeat('B', 51),
        'property_custodian' => str_repeat('C', 51),
        'primary_mobile_no' => str_repeat('1', 16),
        'secondary_mobile_no' => str_repeat('2', 16),
        'email' => str_repeat('a', 43).'@example.com',
    ]);

    $response->assertSessionHasErrors([
        'school_head',
        'librarian',
        'property_custodian',
        'primary_mobile_no',
        'secondary_mobile_no',
        'email',
    ]);
});

test('school activation request is persisted and admin can manually activate when smtp is unavailable', function () {
    $municipality = Municipality::factory()->create(['name' => 'Maco']);
    $district = District::factory()->create(['municipality_id' => $municipality->id, 'name' => 'District 1']);
    $barangay = Barangay::factory()->create(['municipality_id' => $municipality->id, 'name' => 'Lapu-lapu']);

    $school = School::factory()->create([
        'district_id' => null,
        'municipality_id' => null,
        'school_id' => 'SID-55555',
        'is_activated' => false,
    ]);

    Mail::fake();

    $editPage = $this->get(route('school.activate.edit', $school));

    $editPage
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolActivationPage')
            ->where('otpEnabled', false)
        );

    $activationResponse = $this->post(route('school.activate.store', $school), [
        'school_head' => 'MANUAL HEAD',
        'librarian' => 'MANUAL LIBRARIAN',
        'property_custodian' => 'MANUAL CUSTODIAN',
        'email' => 'manual-school@example.com',
        'municipality_id' => $municipality->id,
        'district_id' => $district->id,
        'barangay_id' => $barangay->id,
        'primary_mobile_no' => '09171234567',
        'secondary_mobile_no' => '09281234567',
    ]);

    $activationResponse
        ->assertRedirect(route('school.activate.edit', $school))
        ->assertSessionHas('status');

    $school->refresh();

    expect($school->is_activated)->toBeFalse();
    expect($school->school_head)->toBe('MANUAL HEAD');
    expect($school->email)->toBe('manual-school@example.com');
    expect($school->activation_requested_at)->not->toBeNull();

    Mail::assertNothingSent();

    $admin = User::factory()->admin()->create();

    $manualActivationResponse = $this
        ->actingAs($admin)
        ->post(route('admin.schools.manual-activate', $school));

    $manualActivationResponse->assertRedirect(route('admin.schools.show', $school));

    $school->refresh();

    expect($school->is_activated)->toBeTrue();
    expect($school->activation_requested_at)->toBeNull();

    $user = User::where('email', 'manual-school@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->role)->toBe('school');
    expect($user->school_id)->toBe($school->id);
});

test('admin manual activation emails credentials when smtp is enabled', function () {
    Mail::fake();

    AppSetting::query()->updateOrCreate(['key' => 'smtp_enabled'], ['value' => '1']);
    AppSetting::query()->updateOrCreate(['key' => 'smtp_host'], ['value' => 'smtp.example.test']);
    AppSetting::query()->updateOrCreate(['key' => 'smtp_port'], ['value' => '587']);

    $school = School::factory()->create([
        'school_id' => 'SID-66666',
        'is_activated' => false,
        'school_head' => 'Manual Head',
        'email' => 'manual-enabled@example.com',
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.schools.manual-activate', $school))
        ->assertRedirect(route('admin.schools.show', $school));

    Mail::assertSent(SchoolActivationCredentialsMail::class, function (SchoolActivationCredentialsMail $mail): bool {
        return $mail->email === 'manual-enabled@example.com';
    });
});

test('admin can resend generated credentials via the send credentials button', function () {
    Mail::fake();

    AppSetting::query()->updateOrCreate(['key' => 'smtp_enabled'], ['value' => '1']);
    AppSetting::query()->updateOrCreate(['key' => 'smtp_host'], ['value' => 'smtp.example.test']);
    AppSetting::query()->updateOrCreate(['key' => 'smtp_port'], ['value' => '587']);

    $school = School::factory()->create([
        'school_id' => 'SID-88888',
        'is_activated' => false,
        'school_head' => 'Resend Head',
        'email' => 'resend@example.com',
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.schools.manual-activate', $school))
        ->assertRedirect(route('admin.schools.show', $school));

    Mail::assertSent(SchoolActivationCredentialsMail::class, 1);

    $this->actingAs($admin)
        ->post(route('admin.schools.send-credentials', $school))
        ->assertRedirect(route('admin.schools.show', $school))
        ->assertSessionHas('generatedEmail', 'resend@example.com')
        ->assertSessionHas('status', 'Credentials have been emailed to the school.');

    Mail::assertSent(SchoolActivationCredentialsMail::class, 2);
});

test('sending credentials fails gracefully when nothing has been generated yet', function () {
    $school = School::factory()->create([
        'school_id' => 'SID-99991',
        'is_activated' => true,
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.schools.send-credentials', $school))
        ->assertRedirect(route('admin.schools.show', $school))
        ->assertSessionHas('status', 'No generated credentials available to email. Activate the school again to generate new credentials.');
});
