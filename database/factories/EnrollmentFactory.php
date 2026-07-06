<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'school_year_id' => SchoolYear::factory(),
            'grade_level_id' => GradeLevel::factory(),
            'male_count' => fake()->numberBetween(0, 200),
            'female_count' => fake()->numberBetween(0, 200),
        ];
    }
}
