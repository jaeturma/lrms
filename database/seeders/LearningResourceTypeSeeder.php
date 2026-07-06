<?php

namespace Database\Seeders;

use App\Models\LearningResourceType;
use Illuminate\Database\Seeder;

class LearningResourceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'Book',
            'Module',
            'Workbook',
            'Teacher Guide',
            'Supplementary Material',
        ];

        foreach ($types as $type) {
            LearningResourceType::query()->updateOrCreate(
                ['name' => $type],
                ['is_active' => true],
            );
        }
    }
}
