<?php

namespace Database\Factories;

use App\Models\LearningResource;
use App\Models\LearningResourceType;
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
            'learning_resource_type_id' => LearningResourceType::factory(),
            'title' => fake()->sentence(3),
            'publisher' => fake()->company(),
            'quantity_delivered' => fake()->numberBetween(1, 100),
            'quantity_with_issue_defect' => fake()->numberBetween(0, 10),
            'remarks' => fake()->optional()->sentence(4),
        ];
    }
}
