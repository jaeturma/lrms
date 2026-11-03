<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the initial administrator from the auth.initial_admin config
     * (ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD). An administrator that already
     * exists is left untouched, so re-seeding never resets its password or
     * profile. When the account is new and the credentials are missing or
     * invalid, no account is created and the remaining seeders still run.
     * The password is never written to output or logs.
     */
    public function run(): void
    {
        /** @var array{name: ?string, email: ?string, password: ?string} $credentials */
        $credentials = config('auth.initial_admin');

        $email = mb_strtolower(trim((string) $credentials['email']));
        $name = trim((string) $credentials['name']) ?: 'System Administrator';
        $password = (string) $credentials['password'];

        if ($email !== '' && User::query()->where('email', $email)->exists()) {
            $this->command?->info("Administrator {$email} already exists; leaving it unchanged.");

            return;
        }

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            [
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', Password::defaults()],
            ],
            [],
            ['email' => 'ADMIN_EMAIL', 'password' => 'ADMIN_PASSWORD'],
        );

        if ($validator->fails()) {
            $this->command?->warn('Initial administrator not created: set valid ADMIN_EMAIL and ADMIN_PASSWORD in the environment and re-run AdminUserSeeder.');

            foreach ($validator->errors()->all() as $message) {
                $this->command?->warn("  - {$message}");
            }

            return;
        }

        User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => 'admin',
                'school_id' => null,
                'email_verified_at' => now(),
            ],
        );

        $this->command?->info("Administrator {$email} created.");
    }
}
