<?php

namespace Database\Seeders;

use App\Models\SmeCatalogItem;
use Illuminate\Database\Seeder;

class SmeCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'item_name' => 'Microscope',
                'category' => 'Science',
                'brand' => null,
                'model' => null,
                'specifications' => 'Compound microscope for science laboratory use.',
                'manufacturer' => null,
                'description' => 'Science equipment for observing specimens during laboratory activities.',
            ],
            [
                'item_name' => 'Science Laboratory Kit',
                'category' => 'Science',
                'brand' => null,
                'model' => null,
                'specifications' => 'Assorted laboratory tools and consumable-ready equipment set.',
                'manufacturer' => null,
                'description' => 'Science kit used for classroom demonstrations and experiments.',
            ],
            [
                'item_name' => 'Mathematics Manipulative Kit',
                'category' => 'Mathematics',
                'brand' => null,
                'model' => null,
                'specifications' => 'Hands-on mathematics learning kit.',
                'manufacturer' => null,
                'description' => 'Manipulatives used for numeracy and mathematics instruction.',
            ],
        ];

        foreach ($items as $item) {
            SmeCatalogItem::query()->updateOrCreate(
                [
                    'item_name' => $item['item_name'],
                    'category' => $item['category'],
                ],
                $item + ['is_active' => true],
            );
        }
    }
}
