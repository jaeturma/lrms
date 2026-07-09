<?php

namespace Database\Factories;

use App\Models\OtherEquipment;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OtherEquipment>
 */
class OtherEquipmentFactory extends Factory
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
            'item_code' => 'OTH-'.fake()->unique()->numerify('######'),
            'item_name' => fake()->randomElement(['Technical-Vocational Tool Kit', 'Library Computer', 'Assistive Learning Device', 'Sports Equipment Set']),
            'category' => fake()->randomElement(OtherEquipment::CATEGORIES),
            'brand' => fake()->company(),
            'model' => strtoupper(fake()->bothify('??-###')),
            'serial_number' => strtoupper(fake()->bothify('SN########')),
            'condition' => 'Good',
            'status' => 'Available',
        ];
    }
}
