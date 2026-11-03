<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class RoleUserSeeder extends Seeder
{
    /**
     * One account per staff role.
     *
     * @var list<array{name: string, email: string, role: string}>
     */
    private const USERS = [
        ['name' => 'Learning Resource Manager', 'email' => 'manager@lrms.com', 'role' => 'manager'],
        ['name' => 'School Librarian', 'email' => 'librarian@lrms.com', 'role' => 'librarian'],
        ['name' => 'Division Property Custodian', 'email' => 'supply@lrms.com', 'role' => 'supply'],
        ['name' => 'Curriculum Chief', 'email' => 'cidchief@lrms.com', 'role' => 'cidchief'],
        ['name' => 'Assistant Schools Division Superintendent', 'email' => 'asds@lrms.com', 'role' => 'asds'],
        ['name' => 'Schools Division Superintendent', 'email' => 'sds@lrms.com', 'role' => 'sds'],
        ['name' => 'ICT Officer', 'email' => 'ito@lrms.com', 'role' => 'ito'],
        ['name' => 'System Administrator', 'email' => 'sysadmin@lrms.com', 'role' => 'sysadmin'],
        ['name' => 'Super Administrator', 'email' => 'superadmin@lrms.com', 'role' => 'superadmin'],
    ];

    /**
     * Create any missing staff role account with the initial password from
     * the auth.initial_staff_password config (STAFF_PASSWORD). Existing
     * accounts are left untouched, so re-seeding never resets a password or
     * profile. When accounts are missing and the password is missing or
     * invalid, none are created and the remaining seeders still run. The
     * password is never written to output or logs.
     */
    public function run(): void
    {
        $existingEmails = User::query()
            ->whereIn('email', array_column(self::USERS, 'email'))
            ->pluck('email')
            ->map(fn (string $email): string => mb_strtolower($email))
            ->all();

        $missing = array_values(array_filter(
            self::USERS,
            fn (array $user): bool => ! in_array($user['email'], $existingEmails, true),
        ));

        if ($missing === []) {
            $this->command?->info('Staff role accounts already exist; leaving them unchanged.');

            return;
        }

        $password = (string) config('auth.initial_staff_password');

        $validator = Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', Password::defaults()]],
            [],
            ['password' => 'STAFF_PASSWORD'],
        );

        if ($validator->fails()) {
            $this->command?->warn('Staff role accounts not created: set a valid STAFF_PASSWORD in the environment and re-run RoleUserSeeder.');

            foreach ($validator->errors()->all() as $message) {
                $this->command?->warn("  - {$message}");
            }

            return;
        }

        foreach ($missing as $user) {
            User::query()->firstOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make($password),
                    'role' => $user['role'],
                    'school_id' => null,
                    'email_verified_at' => now(),
                ],
            );
        }

        $this->command?->info(count($missing).' staff role account(s) created: '.implode(', ', array_column($missing, 'email')).'.');
    }
}
