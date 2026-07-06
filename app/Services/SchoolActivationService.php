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
     * @param  array{school_head: string, librarian?: string|null, property_custodian?: string|null, email: string, primary_mobile_no?: string|null, secondary_mobile_no?: string|null, municipality_id?: int|null, district_id?: int|null, barangay_id?: int|null}  $payload
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
                'municipality_id' => $payload['municipality_id'] ?? $school->municipality_id,
                'district_id' => $payload['district_id'] ?? $school->district_id,
                'barangay_id' => $payload['barangay_id'] ?? $school->barangay_id,
                'school_head' => $payload['school_head'],
                'librarian' => $payload['librarian'] ?? null,
                'property_custodian' => $payload['property_custodian'] ?? null,
                'primary_mobile_no' => $payload['primary_mobile_no'] ?? null,
                'secondary_mobile_no' => $payload['secondary_mobile_no'] ?? null,
                'email' => $payload['email'],
                'user_id' => $user->id,
                'is_activated' => true,
                'activation_requested_at' => null,
            ]);

            return [
                'user' => $user,
                'password' => $plainPassword,
            ];
        });
    }
}
