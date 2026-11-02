<?php

namespace Database\Factories;

use App\Models\ValidLrSchool;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ValidLrSchool>
 */
class ValidLrSchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'beis_school_id' => (string) fake()->unique()->numberBetween(100000, 599999),
            'region' => 'Region XI',
            'division' => 'Davao De Oro',
            'district' => fake()->city(),
            'school_name' => fake()->lastName().' Elementary School',
            'province' => 'DAVAO DE ORO',
            'municipality' => strtoupper(fake()->city()),
            'barangay' => strtoupper(fake()->streetName()),
            'sector' => 'Public',
            'school_type' => 'School with no Annexes',
            'curricular_offering' => 'Purely ES',
            'sme_deliveries' => [],
        ];
    }

    public function secondary(): static
    {
        return $this->state(fn (): array => [
            'school_name' => fake()->lastName().' National High School',
            'curricular_offering' => 'JHS with SHS',
        ]);
    }
}
