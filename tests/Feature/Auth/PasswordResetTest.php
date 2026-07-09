<?php

use App\Mail\PasswordResetOtpMail;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

function enableSmtpForPasswordReset(): void
{
    AppSetting::query()->updateOrCreate(['key' => 'smtp_enabled'], ['value' => '1']);
    AppSetting::query()->updateOrCreate(['key' => 'smtp_host'], ['value' => 'smtp.example.test']);
    AppSetting::query()->updateOrCreate(['key' => 'smtp_port'], ['value' => '587']);
}

test('forgot password screen can be rendered', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk();
});

test('password reset otp can be requested and used to set a new password', function () {
    enableSmtpForPasswordReset();
    Mail::fake();

    $user = User::factory()->create();

    $this->post(route('password.otp.send'), ['email' => $user->email])
        ->assertRedirect(route('password.otp.verify'))
        ->assertSessionHas('otpEmail', $user->email);

    $otp = null;
    Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$otp): bool {
        $otp = $mail->otp;

        return true;
    });

    expect($otp)->not->toBeNull();

    $verifyPage = $this->get(route('password.otp.verify'));
    $verifyPage->assertOk();

    $response = $this->post(route('password.otp.update'), [
        'email' => $user->email,
        'otp' => $otp,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('login'));

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
});

test('password cannot be reset with an invalid otp', function () {
    enableSmtpForPasswordReset();
    Mail::fake();

    $user = User::factory()->create();

    $this->post(route('password.otp.send'), ['email' => $user->email]);

    $response = $this->post(route('password.otp.update'), [
        'email' => $user->email,
        'otp' => '000000',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertSessionHasErrors('otp');
});

test('password cannot be reset without first requesting an otp', function () {
    $user = User::factory()->create();

    $response = $this->post(route('password.otp.update'), [
        'email' => $user->email,
        'otp' => '123456',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertSessionHasErrors('otp');
});

test('password reset otp request fails when smtp is not enabled', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->post(route('password.otp.send'), ['email' => $user->email])
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('status');

    Mail::assertNothingSent();
});

test('verify otp page redirects back to request page without a pending otp', function () {
    $response = $this->get(route('password.otp.verify'));

    $response->assertRedirect(route('password.request'));
});
