<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
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

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('Pass1234'),
                    'role' => $user['role'],
                    'school_id' => null,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
