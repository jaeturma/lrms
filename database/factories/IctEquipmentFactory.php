<?php

namespace Database\Factories;

use App\Models\IctEquipment;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IctEquipment>
 */
class IctEquipmentFactory extends Factory
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
            'item_code' => 'ICT-'.fake()->unique()->numerify('######'),
            'item_name' => fake()->randomElement(['Laptop', 'Desktop Computer', 'Tablet', 'Mobile Phone', 'Printer', 'Projector', 'Smart TV']),
            'category' => fake()->randomElement(IctEquipment::CATEGORIES),
            'brand' => fake()->company(),
            'model' => strtoupper(fake()->bothify('??-###')),
            'serial_number' => strtoupper(fake()->bothify('SN########')),
            'condition' => 'Good',
            'status' => 'Available',
        ];
    }
}
