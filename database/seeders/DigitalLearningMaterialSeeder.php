<?php

namespace Database\Seeders;

use App\Models\DigitalLearningMaterial;
use Illuminate\Database\Seeder;

class DigitalLearningMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'name' => 'Grade 7 Science Interactive Module',
                'category' => 'Learning Material',
                'type' => 'H5P (HTML5 Package)',
                'publisher' => 'DepEd Learning Resources',
                'link' => 'https://commons.deped.gov.ph/example-science-module',
                'description' => 'Interactive H5P module covering matter and energy for Grade 7 Science.',
                'quality_assured' => true,
            ],
            [
                'name' => 'Araling Panlipunan Digital Storybook Grade 3',
                'category' => 'Learning Material',
                'type' => 'Digital Storybook',
                'publisher' => 'DepEd Learning Resources',
                'link' => 'https://commons.deped.gov.ph/example-storybook',
                'description' => 'Digital storybook used for Grade 3 Araling Panlipunan reading activities.',
                'quality_assured' => true,
            ],
            [
                'name' => 'Mathematics 6 Weekly Lesson Plan Set',
                'category' => 'Lesson Plan',
                'type' => 'Word Document',
                'publisher' => 'Curriculum Implementation Division',
                'link' => null,
                'description' => 'Weekly lesson plan set for Grade 6 Mathematics, First Quarter.',
                'quality_assured' => false,
            ],
            [
                'name' => 'English 8 First Quarter Test Questions',
                'category' => 'Assessment/Test Material',
                'type' => 'PDF',
                'publisher' => 'Curriculum Implementation Division',
                'link' => null,
                'description' => 'First quarter summative test questions for Grade 8 English.',
                'quality_assured' => false,
            ],
            [
                'name' => 'Filipino Learning Worksheet Grade 4',
                'category' => 'Learning Material',
                'type' => 'Learning Worksheet',
                'publisher' => 'DepEd Learning Resources',
                'link' => null,
                'description' => 'Printable/digital worksheet for Grade 4 Filipino reading comprehension.',
                'quality_assured' => true,
            ],
            [
                'name' => 'Science Concepts Educational E-Comic',
                'category' => 'Learning Material',
                'type' => 'Educational E-Comic',
                'publisher' => 'DepEd Learning Resources',
                'link' => 'https://commons.deped.gov.ph/example-ecomic',
                'description' => 'E-comic explaining basic science concepts for elementary learners.',
                'quality_assured' => true,
            ],
        ];

        foreach ($items as $item) {
            DigitalLearningMaterial::query()->updateOrCreate(
                [
                    'name' => $item['name'],
                    'type' => $item['type'],
                ],
                $item + ['is_active' => true],
            );
        }
    }
}
