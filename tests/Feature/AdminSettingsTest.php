<?php

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admin can update branding and smtp settings', function () {
    $admin = User::factory()->admin()->create();
    Storage::fake('public');

    $response = $this
        ->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'login_title' => 'LRMS Login',
            'login_logo_file' => UploadedFile::fake()->image('login-logo.png'),
            'app_title' => 'LRMS App',
            'app_logo_file' => UploadedFile::fake()->image('app-logo.png'),
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'smtp_username' => 'otp@example.com',
            'smtp_password' => 'secret-app-password',
            'smtp_encryption' => 'tls',
            'smtp_from_address' => 'otp@example.com',
            'smtp_from_name' => 'LRMS OTP',
        ]);

    $response->assertRedirect();

    $loginLogoUrl = AppSetting::query()->where('key', 'login_logo_url')->value('value');
    $appLogoUrl = AppSetting::query()->where('key', 'app_logo_url')->value('value');

    expect(AppSetting::query()->where('key', 'login_title')->value('value'))->toBe('LRMS Login');
    expect(AppSetting::query()->where('key', 'app_title')->value('value'))->toBe('LRMS App');
    expect(AppSetting::query()->where('key', 'smtp_host')->value('value'))->toBe('smtp.gmail.com');
    expect(AppSetting::query()->where('key', 'smtp_password_encrypted')->value('value'))->not->toBeNull();
    expect($loginLogoUrl)->toStartWith('/storage/branding/');
    expect($appLogoUrl)->toStartWith('/storage/branding/');

    Storage::disk('public')->assertExists(str_replace('/storage/', '', (string) $loginLogoUrl));
    Storage::disk('public')->assertExists(str_replace('/storage/', '', (string) $appLogoUrl));
});

test('admin settings accepts multipart method spoof update with required titles', function () {
    $admin = User::factory()->admin()->create();
    Storage::fake('public');

    $response = $this
        ->actingAs($admin)
        ->post(route('admin.settings.update'), [
            '_method' => 'put',
            'login_title' => 'Updated Login Title',
            'app_title' => 'Updated App Title',
            'login_logo_file' => UploadedFile::fake()->image('new-login-logo.png'),
        ]);

    $response->assertSessionDoesntHaveErrors(['login_title', 'app_title']);
    $response->assertRedirect();

    expect(AppSetting::query()->where('key', 'login_title')->value('value'))->toBe('Updated Login Title');
    expect(AppSetting::query()->where('key', 'app_title')->value('value'))->toBe('Updated App Title');
});
