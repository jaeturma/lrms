<?php

namespace Database\Factories;

use App\Models\LearningResource;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningResource>
 */
class LearningResourceFactory extends Factory
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
            'resource_type' => fake()->randomElement(['Book', 'Module', 'Workbook']),
            'issue_defect' => fake()->randomElement(['Missing pages', 'Torn cover', 'Unreadable print']),
            'quantity' => fake()->numberBetween(1, 100),
            'publisher' => fake()->company(),
        ];
    }
}
