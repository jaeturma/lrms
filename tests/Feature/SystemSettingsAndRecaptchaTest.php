<?php

use App\Models\AppSetting;
use App\Models\District;
use App\Models\Municipality;
use App\Models\School;
use App\Models\User;
use App\Services\RecaptchaService;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RoleUserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function enableRecaptcha(): void
{
    AppSetting::query()->updateOrCreate(['key' => 'recaptcha_enabled'], ['value' => '1']);
    AppSetting::query()->updateOrCreate(['key' => 'recaptcha_site_key'], ['value' => 'site-key-123']);
    AppSetting::query()->updateOrCreate(['key' => 'recaptcha_secret_encrypted'], ['value' => Crypt::encryptString('secret-key-456')]);
}

test('system admin sets the color theme, website logo and recaptcha keys', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'sysadmin']);

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'login_title' => 'LRMS Login',
            'app_title' => 'LRMS',
            'color_theme' => 'forest',
            'website_logo_file' => UploadedFile::fake()->image('website-logo.png'),
            'recaptcha_enabled' => true,
            'recaptcha_site_key' => 'site-key-123',
            'recaptcha_secret_key' => 'secret-key-456',
        ])
        ->assertSessionHasNoErrors();

    $websiteLogoUrl = (string) AppSetting::query()->where('key', 'website_logo_url')->value('value');
    expect($websiteLogoUrl)->toStartWith('/storage/branding/');
    Storage::disk('public')->assertExists(str_replace('/storage/', '', $websiteLogoUrl));
    expect(Crypt::decryptString((string) AppSetting::query()->where('key', 'recaptcha_secret_encrypted')->value('value')))->toBe('secret-key-456');

    $this->actingAs($admin)
        ->get(route('admin.settings.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('settings.color_theme', 'forest')
            ->where('settings.recaptcha_enabled', true)
            ->where('settings.recaptcha_secret_set', true)
            ->missing('settings.recaptcha_secret_key')
            ->where('branding.color_theme', 'forest')
            ->where('branding.website_logo_url', $websiteLogoUrl)
            ->where('recaptcha.enabled', true)
            ->where('recaptcha.site_key', 'site-key-123'));

    // Saving again without a secret keeps the stored one.
    $this->actingAs($admin)->put(route('admin.settings.update'), [
        'login_title' => 'LRMS Login',
        'app_title' => 'LRMS',
        'recaptcha_enabled' => true,
        'recaptcha_site_key' => 'site-key-123',
    ]);
    expect(app(RecaptchaService::class)->active())->toBeTrue();
});

test('settings reject an unknown color theme and only system admins can change them', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.settings.update'), ['login_title' => 'x', 'app_title' => 'y', 'color_theme' => 'neon'])
        ->assertSessionHasErrors('color_theme');

    $this->actingAs(User::factory()->create(['role' => 'manager']))
        ->put(route('admin.settings.update'), ['login_title' => 'x', 'app_title' => 'y'])
        ->assertForbidden();
});

test('test mail explains when smtp is disabled', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.settings.test-mail'), ['test_email' => 'someone@example.com'])
        ->assertSessionHasErrors('test_email');

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.settings.test-mail'), ['test_email' => 'not-an-email'])
        ->assertSessionHasErrors('test_email');
});

test('admin login requires a passing recaptcha when it is enabled', function () {
    enableRecaptcha();
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com', 'password' => Hash::make('Pass1234!')]);

    $this->post(route('admin.login.store'), ['email' => 'admin@example.com', 'password' => 'Pass1234!'])
        ->assertSessionHasErrors('g-recaptcha-response');
    $this->assertGuest();

    Http::fake([
        RecaptchaService::VERIFY_URL => fn ($request) => Http::response(['success' => $request['response'] === 'good-token']),
    ]);
    $this->post(route('admin.login.store'), ['email' => 'admin@example.com', 'password' => 'Pass1234!', 'g-recaptcha-response' => 'bad-token'])
        ->assertSessionHasErrors('g-recaptcha-response');
    $this->assertGuest();

    $this->post(route('admin.login.store'), ['email' => 'admin@example.com', 'password' => 'Pass1234!', 'g-recaptcha-response' => 'good-token'])
        ->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($admin);

    Http::assertSent(fn ($request) => $request['secret'] === 'secret-key-456' && $request['response'] === 'good-token');
});

test('school login and forgot password also check recaptcha when enabled', function () {
    enableRecaptcha();
    User::factory()->create(['role' => 'school', 'email' => 'school@example.com', 'password' => Hash::make('Pass1234!')]);

    $this->post('/login', ['email' => 'school@example.com', 'password' => 'Pass1234!'])
        ->assertSessionHasErrors('g-recaptcha-response');
    $this->assertGuest();

    $this->post(route('password.otp.send'), ['email' => 'school@example.com'])
        ->assertSessionHasErrors('g-recaptcha-response');
});

test('recaptcha is not required while disabled', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com', 'password' => Hash::make('Pass1234!')]);

    $this->post(route('admin.login.store'), ['email' => 'admin@example.com', 'password' => 'Pass1234!'])
        ->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($admin);
});

test('a school can upload and remove its logo', function () {
    Storage::fake('public');
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);
    $school = School::factory()->create(['district_id' => $district->id, 'municipality_id' => $municipality->id]);
    $user = User::factory()->create(['role' => 'school', 'school_id' => $school->id]);

    $this->actingAs($user)
        ->post(route('school.logo.store'), ['logo' => UploadedFile::fake()->image('logo.png')])
        ->assertSessionHasNoErrors();

    $school->refresh();
    expect($school->logo_path)->toStartWith('school-logos/');
    Storage::disk('public')->assertExists($school->logo_path);
    expect($school->logoUrl())->toEndWith($school->logo_path);

    $this->actingAs($user)
        ->post(route('school.logo.store'), ['logo' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors('logo');

    $path = $school->logo_path;
    $this->actingAs($user)->delete(route('school.logo.destroy'))->assertSessionHasNoErrors();

    expect($school->refresh()->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('school.logo.store'), ['logo' => UploadedFile::fake()->image('logo.png')])
        ->assertForbidden();
});

test('seeded admin and staff accounts use the configured initial passwords', function () {
    config(['auth.initial_admin.password' => 'Configured-Admin-1', 'auth.initial_staff_password' => 'Configured-Staff-1']);

    $this->seed([AdminUserSeeder::class, RoleUserSeeder::class]);

    expect(Hash::check('Configured-Admin-1', User::query()->where('email', 'admin@lrms.com')->sole()->password))->toBeTrue();

    foreach (['manager@lrms.com', 'sysadmin@lrms.com', 'superadmin@lrms.com', 'ito@lrms.com'] as $email) {
        expect(Hash::check('Configured-Staff-1', User::query()->where('email', $email)->sole()->password))->toBeTrue();
    }
});

test('branding defaults to the iLRMS title', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('branding.app_title', 'iLRMS - Integrated Learning Resource Management System'));
});
