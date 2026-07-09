<?php

namespace Database\Factories;

use App\Models\IctEquipment;
use App\Models\IctEquipmentCatalogItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IctEquipmentCatalogItem>
 */
class IctEquipmentCatalogItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_name' => fake()->randomElement(['Laptop', 'Desktop Computer', 'Tablet', 'Mobile Phone', 'Printer', 'Projector', 'Smart TV']),
            'category' => fake()->randomElement(IctEquipment::CATEGORIES),
            'brand' => fake()->company(),
            'model' => strtoupper(fake()->bothify('??-###')),
            'specifications' => fake()->sentence(),
            'manufacturer' => fake()->company(),
            'is_active' => true,
        ];
    }
}
