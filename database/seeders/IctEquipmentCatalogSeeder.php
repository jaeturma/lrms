<?php

namespace Database\Seeders;

use App\Models\IctEquipmentCatalogItem;
use Illuminate\Database\Seeder;

class IctEquipmentCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'item_name' => 'Laptop Computer',
                'category' => 'Laptop',
                'brand' => null,
                'model' => null,
                'specifications' => 'Portable computer for teacher or learner use.',
                'manufacturer' => null,
                'description' => 'General-purpose laptop used for instruction, administration, or learning activities.',
            ],
            [
                'item_name' => 'Desktop Computer',
                'category' => 'Desktop',
                'brand' => null,
                'model' => null,
                'specifications' => 'Desktop computer set with monitor, keyboard, and mouse.',
                'manufacturer' => null,
                'description' => 'Computer laboratory or office workstation.',
            ],
            [
                'item_name' => 'Tablet',
                'category' => 'Tablet',
                'brand' => null,
                'model' => null,
                'specifications' => 'Portable touchscreen learning device.',
                'manufacturer' => null,
                'description' => 'Tablet device used for digital learning activities.',
            ],
            [
                'item_name' => 'Mobile Phone',
                'category' => 'Mobile Phone',
                'brand' => null,
                'model' => null,
                'specifications' => 'Mobile handset used for learning or coordination activities.',
                'manufacturer' => null,
                'description' => 'Mobile phone issued for instructional or administrative use.',
            ],
            [
                'item_name' => 'LCD Projector',
                'category' => 'Projector',
                'brand' => null,
                'model' => null,
                'specifications' => 'Multimedia projector for classroom presentation.',
                'manufacturer' => null,
                'description' => 'Classroom projector used for visual instruction.',
            ],
            [
                'item_name' => 'Smart TV',
                'category' => 'Smart TV',
                'brand' => null,
                'model' => null,
                'specifications' => 'Smart television display for instructional media.',
                'manufacturer' => null,
                'description' => 'Interactive or media display used in classrooms and learning spaces.',
            ],
            [
                'item_name' => 'Printer',
                'category' => 'Printer',
                'brand' => null,
                'model' => null,
                'specifications' => 'Document printer for school learning and administrative materials.',
                'manufacturer' => null,
                'description' => 'Printer used for producing learning resources and reports.',
            ],
        ];

        foreach ($items as $item) {
            IctEquipmentCatalogItem::query()->updateOrCreate(
                [
                    'item_name' => $item['item_name'],
                    'category' => $item['category'],
                ],
                $item + ['is_active' => true],
            );
        }
    }
}
