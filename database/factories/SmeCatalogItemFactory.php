<?php

namespace Database\Factories;

use App\Models\Sme;
use App\Models\SmeCatalogItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmeCatalogItem>
 */
class SmeCatalogItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_name' => fake()->randomElement(['Microscope', 'Science Laboratory Kit', 'Mathematics Manipulative Kit']),
            'category' => fake()->randomElement(Sme::CATEGORIES),
            'brand' => fake()->company(),
            'model' => strtoupper(fake()->bothify('??-###')),
            'specifications' => fake()->sentence(),
            'manufacturer' => fake()->company(),
            'is_active' => true,
        ];
    }
}
