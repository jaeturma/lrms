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
            ['name' => 'Learning Resource Manager', 'email' => 'manager@lrms.com'],
            ['name' => 'Division Property Custodian', 'email' => 'supply@lrms.com'],
            ['name' => 'Curriculum Chief', 'email' => 'cidchief@lrms.com'],
            ['name' => 'Division Superintendent', 'email' => 'sds@lrms.com'],
            ['name' => 'ICT Officer', 'email' => 'ito@lrms.com'],
            ['name' => 'System Administrator', 'email' => 'sysadmin@lrms.com'],
            ['name' => 'Super Administrator', 'email' => 'superadmin@lrms.com'],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('Pass1234'),
                    'role' => 'admin',
                    'school_id' => null,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
