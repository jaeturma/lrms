<?php

namespace Database\Factories;

use App\Models\District;
use App\Models\Municipality;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'district_id' => District::factory(),
            'municipality_id' => Municipality::factory(),
            'barangay_id' => null,
            'school_id' => fake()->unique()->numerify('SID-#####'),
            'school_name' => fake()->company().' Elementary School',
            'school_type' => fake()->randomElement(School::SCHOOL_TYPES),
            'school_head' => null,
            'librarian' => null,
            'property_custodian' => null,
            'primary_mobile_no' => null,
            'secondary_mobile_no' => null,
            'email' => null,
            'user_id' => null,
            'is_activated' => false,
        ];
    }
}
