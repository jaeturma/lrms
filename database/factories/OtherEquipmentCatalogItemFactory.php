<?php

namespace Database\Factories;

use App\Models\OtherEquipment;
use App\Models\OtherEquipmentCatalogItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OtherEquipmentCatalogItem>
 */
class OtherEquipmentCatalogItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_name' => fake()->randomElement(['Technical-Vocational Tool Kit', 'Library Computer', 'Assistive Learning Device', 'Sports Equipment Set']),
            'category' => fake()->randomElement(OtherEquipment::CATEGORIES),
            'brand' => fake()->company(),
            'model' => strtoupper(fake()->bothify('??-###')),
            'specifications' => fake()->sentence(),
            'manufacturer' => fake()->company(),
            'is_active' => true,
        ];
    }
}
