<?php

namespace Database\Factories;

use App\Models\DigitalLearningMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DigitalLearningMaterial>
 */
class DigitalLearningMaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(4),
            'category' => fake()->randomElement(DigitalLearningMaterial::CATEGORIES),
            'type' => fake()->randomElement(DigitalLearningMaterial::TYPES),
            'publisher' => fake()->company(),
            'link' => fake()->url(),
            'description' => fake()->sentence(),
            'quality_assured' => fake()->boolean(),
            'is_active' => true,
        ];
    }
}
