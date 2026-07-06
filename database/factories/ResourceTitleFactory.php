<?php

namespace Database\Factories;

use App\Models\LearningResourceType;
use App\Models\ResourceTitle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResourceTitle>
 */
class ResourceTitleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'learning_resource_type_id' => LearningResourceType::factory(),
            'grade_level_id' => null,
            'title' => fake()->sentence(4),
            'author' => fake()->name(),
            'publisher' => fake()->company(),
            'language' => fake()->randomElement(['English', 'Filipino', 'Tagalog']),
            'subject' => fake()->randomElement(['Mathematics', 'Science', 'English', 'Values Education']),
            'copyright_year' => fake()->numberBetween(2015, 2026),
            'pages' => fake()->numberBetween(50, 400),
            'isbn' => fake()->unique()->isbn13(),
            'is_active' => true,
        ];
    }
}
