<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
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
            'item_code' => 'EQP-'.fake()->unique()->numerify('######'),
            'item_name' => fake()->randomElement(['Laptop', 'Projector', 'Smart TV', 'Microscope', 'Tablet']),
            'category' => fake()->randomElement(Equipment::CATEGORIES),
            'brand' => fake()->company(),
            'model' => strtoupper(fake()->bothify('??-###')),
            'serial_number' => strtoupper(fake()->bothify('SN########')),
            'condition' => 'Good',
            'status' => 'Available',
        ];
    }
}
