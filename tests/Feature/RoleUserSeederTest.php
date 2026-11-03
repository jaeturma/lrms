<?php

use App\Models\User;
use Database\Seeders\RoleUserSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

const STAFF_ROLES = ['manager', 'librarian', 'supply', 'cidchief', 'asds', 'sds', 'ito', 'sysadmin', 'superadmin'];

beforeEach(function () {
    config(['auth.initial_staff_password' => 'Initial-Staff-Secret-1']);
});

test('it creates one verified account per staff role with the configured password', function () {
    $this->seed(RoleUserSeeder::class);

    $users = User::query()->get();

    expect($users)->toHaveCount(count(STAFF_ROLES))
        ->and($users->pluck('role')->sort()->values()->all())->toBe(collect(STAFF_ROLES)->sort()->values()->all());

    $users->each(function (User $user): void {
        expect($user->school_id)->toBeNull()
            ->and($user->email_verified_at)->not->toBeNull()
            ->and(Hash::check('Initial-Staff-Secret-1', $user->password))->toBeTrue();
    });
});

test('re-seeding never resets an existing staff password or profile', function () {
    $this->seed(RoleUserSeeder::class);

    $sysadmin = User::query()->where('email', 'sysadmin@lrms.com')->sole();
    $sysadmin->forceFill(['name' => 'Renamed In Production', 'password' => Hash::make('Changed-By-Owner-2')])->save();

    config(['auth.initial_staff_password' => 'A-Different-Secret-3']);
    $this->seed(RoleUserSeeder::class);

    $sysadmin->refresh();

    expect(User::query()->count())->toBe(count(STAFF_ROLES))
        ->and($sysadmin->name)->toBe('Renamed In Production')
        ->and(Hash::check('Changed-By-Owner-2', $sysadmin->password))->toBeTrue();
});

test('only missing staff accounts are created and existing ones are untouched', function () {
    $existing = User::factory()->create(['email' => 'manager@lrms.com', 'role' => 'manager', 'password' => Hash::make('Existing-Secret-4')]);

    $this->seed(RoleUserSeeder::class);

    expect(User::query()->count())->toBe(count(STAFF_ROLES))
        ->and(Hash::check('Existing-Secret-4', $existing->fresh()->password))->toBeTrue();
});

test('existing staff accounts are left alone even when no password is configured', function () {
    $this->seed(RoleUserSeeder::class);
    config(['auth.initial_staff_password' => null]);

    $this->artisan('db:seed', ['--class' => RoleUserSeeder::class])
        ->expectsOutputToContain('already exist')
        ->assertSuccessful();

    expect(Hash::check('Initial-Staff-Secret-1', User::query()->where('email', 'ito@lrms.com')->sole()->password))->toBeTrue();
});

test('it creates no staff account when the password is missing or invalid', function (?string $password) {
    config(['auth.initial_staff_password' => $password]);

    $this->artisan('db:seed', ['--class' => RoleUserSeeder::class])
        ->expectsOutputToContain('Staff role accounts not created')
        ->assertSuccessful();

    expect(User::query()->exists())->toBeFalse();
})->with([
    'missing' => [null],
    'empty' => [''],
    'too short' => ['short'],
]);

test('production requires a strong initial staff password', function () {
    Http::preventStrayRequests();
    Http::fake(['api.pwnedpasswords.com/*' => Http::response('')]);
    app()->detectEnvironment(fn () => 'production');

    config(['auth.initial_staff_password' => 'Pass1234!']);
    $this->artisan('db:seed', ['--class' => RoleUserSeeder::class, '--force' => true])->assertSuccessful();
    expect(User::query()->exists())->toBeFalse();

    config(['auth.initial_staff_password' => 'Strong-Staff-Pass-9']);
    $this->artisan('db:seed', ['--class' => RoleUserSeeder::class, '--force' => true])->assertSuccessful();
    expect(User::query()->count())->toBe(count(STAFF_ROLES));
});

test('the staff password is never written to the console or the log', function () {
    Log::spy();

    $this->artisan('db:seed', ['--class' => RoleUserSeeder::class])
        ->doesntExpectOutputToContain('Initial-Staff-Secret-1')
        ->assertSuccessful();

    User::query()->delete();
    config(['auth.initial_staff_password' => 'weak']);

    $this->artisan('db:seed', ['--class' => RoleUserSeeder::class])
        ->doesntExpectOutputToContain('weak')
        ->assertSuccessful();

    Log::shouldNotHaveReceived('log');
    Log::shouldNotHaveReceived('info');
    Log::shouldNotHaveReceived('warning');
});
