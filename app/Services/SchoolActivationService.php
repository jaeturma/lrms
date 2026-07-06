<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SchoolActivationService
{
    /**
     * @param  array{school_head: string, librarian?: string|null, property_custodian?: string|null, email: string}  $payload
     * @return array{user: User, password: string}
     */
    public function activate(School $school, array $payload): array
    {
        if ($school->is_activated) {
            throw ValidationException::withMessages([
                'school_id' => 'This school has already been activated.',
            ]);
        }

        return DB::transaction(function () use ($school, $payload): array {
            $plainPassword = Str::password(12, letters: true, numbers: true, symbols: false);

            $user = User::create([
                'name' => $school->school_name,
                'email' => $payload['email'],
                'password' => Hash::make($plainPassword),
                'role' => 'school',
                'school_id' => $school->id,
            ]);

            $school->update([
                'school_head' => $payload['school_head'],
                'librarian' => $payload['librarian'] ?? null,
                'property_custodian' => $payload['property_custodian'] ?? null,
                'email' => $payload['email'],
                'user_id' => $user->id,
                'is_activated' => true,
            ]);

            return [
                'user' => $user,
                'password' => $plainPassword,
            ];
        });
    }
}
