<?php

namespace Database\Factories;

use App\Models\ValidLrSchool;
use App\Models\ValidLrVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ValidLrVisit>
 */
class ValidLrVisitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'valid_lr_school_id' => ValidLrSchool::factory(),
            'status' => 'draft',
            'visited_at' => now(),
            'monitoring_team' => fake()->name().', '.fake()->name(),
            'school_representative' => fake()->name(),
            'indicators' => [],
        ];
    }
}
