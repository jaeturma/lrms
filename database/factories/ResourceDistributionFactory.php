<?php

namespace Database\Factories;

use App\Models\LearningResourceType;
use App\Models\ResourceDistribution;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResourceDistribution>
 */
class ResourceDistributionFactory extends Factory
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
            'quantity' => fake()->numberBetween(1, 100),
            'status' => 'pending',
            'created_by' => User::factory()->admin(),
        ];
    }
}
