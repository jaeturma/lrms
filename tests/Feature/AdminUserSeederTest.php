<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config(['auth.initial_admin' => [
        'name' => 'Division Administrator',
        'email' => 'Initial.Admin@Example.com ',
        'password' => 'Initial-Admin-Secret-1',
    ]]);
});

test('it creates the initial administrator from the configured credentials', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::query()->sole();

    expect($admin->email)->toBe('initial.admin@example.com')
        ->and($admin->name)->toBe('Division Administrator')
        ->and($admin->role)->toBe('admin')
        ->and($admin->school_id)->toBeNull()
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and(Hash::check('Initial-Admin-Secret-1', $admin->password))->toBeTrue();
});

test('re-seeding never resets an existing administrator password or profile', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::query()->sole();
    $admin->forceFill([
        'name' => 'Renamed In Production',
        'password' => Hash::make('Changed-By-Admin-2'),
        'email_verified_at' => null,
    ])->save();

    config(['auth.initial_admin.password' => 'A-Different-Secret-3', 'auth.initial_admin.name' => 'Another Name']);
    $this->seed(AdminUserSeeder::class);

    $admin->refresh();

    expect(User::query()->count())->toBe(1)
        ->and($admin->name)->toBe('Renamed In Production')
        ->and($admin->email_verified_at)->toBeNull()
        ->and(Hash::check('Changed-By-Admin-2', $admin->password))->toBeTrue();
});

test('an existing administrator is left alone even when no password is configured', function () {
    $existing = User::factory()->admin()->create(['email' => 'initial.admin@example.com', 'password' => Hash::make('Existing-Secret-4')]);

    config(['auth.initial_admin.password' => null]);

    $this->artisan('db:seed', ['--class' => AdminUserSeeder::class])
        ->expectsOutputToContain('already exists')
        ->assertSuccessful();

    expect(Hash::check('Existing-Secret-4', $existing->fresh()->password))->toBeTrue();
});

test('it does not create an administrator when the credentials are missing or invalid', function (array $override) {
    config(['auth.initial_admin' => [...config('auth.initial_admin'), ...$override]]);

    $this->artisan('db:seed', ['--class' => AdminUserSeeder::class])
        ->expectsOutputToContain('Initial administrator not created')
        ->assertSuccessful();

    expect(User::query()->exists())->toBeFalse();
})->with([
    'missing password' => [['password' => null]],
    'empty password' => [['password' => '']],
    'missing email' => [['email' => null]],
    'invalid email' => [['email' => 'not-an-email']],
    'too short password' => [['password' => 'short']],
]);

test('production requires a strong initial password', function () {
    Http::preventStrayRequests();
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('')]);
    app()->detectEnvironment(fn () => 'production');

    config(['auth.initial_admin.password' => 'Pass1234!']);
    $this->artisan('db:seed', ['--class' => AdminUserSeeder::class, '--force' => true])->assertSuccessful();
    expect(User::query()->exists())->toBeFalse();

    config(['auth.initial_admin.password' => 'Strong-Initial-Pass-9']);
    $this->artisan('db:seed', ['--class' => AdminUserSeeder::class, '--force' => true])->assertSuccessful();
    expect(User::query()->where('email', 'initial.admin@example.com')->exists())->toBeTrue();
});

test('the password is never written to the console or the log', function () {
    Log::spy();

    $this->artisan('db:seed', ['--class' => AdminUserSeeder::class])
        ->doesntExpectOutputToContain('Initial-Admin-Secret-1')
        ->assertSuccessful();

    config(['auth.initial_admin.email' => 'second@example.com', 'auth.initial_admin.password' => 'weak']);

    $this->artisan('db:seed', ['--class' => AdminUserSeeder::class])
        ->doesntExpectOutputToContain('weak')
        ->assertSuccessful();

    Log::shouldNotHaveReceived('log');
    Log::shouldNotHaveReceived('info');
    Log::shouldNotHaveReceived('warning');
});
