<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Sme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sme>
 */
class SmeFactory extends Factory
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
            'item_code' => 'SME-'.fake()->unique()->numerify('######'),
            'item_name' => fake()->randomElement(['Microscope', 'Science Laboratory Kit', 'Mathematics Manipulative Kit']),
            'category' => fake()->randomElement(Sme::CATEGORIES),
            'brand' => fake()->company(),
            'model' => strtoupper(fake()->bothify('??-###')),
            'serial_number' => strtoupper(fake()->bothify('SN########')),
            'condition' => 'Good',
            'status' => 'Available',
        ];
    }
}
