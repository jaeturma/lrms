<?php

namespace Database\Seeders;

use App\Models\OtherEquipmentCatalogItem;
use Illuminate\Database\Seeder;

class OtherEquipmentCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            [
                'item_name' => 'Technical-Vocational Tool Kit',
                'category' => 'TVL',
                'brand' => null,
                'model' => null,
                'specifications' => 'Tool kit for TVL workshop instruction.',
                'manufacturer' => null,
                'description' => 'Equipment set used for technical-vocational livelihood classes.',
            ],
            [
                'item_name' => 'Library Computer',
                'category' => 'Library',
                'brand' => null,
                'model' => null,
                'specifications' => 'Computer terminal for library search and circulation support.',
                'manufacturer' => null,
                'description' => 'Library equipment used by learners or library personnel.',
            ],
            [
                'item_name' => 'Assistive Learning Device',
                'category' => 'SPED',
                'brand' => null,
                'model' => null,
                'specifications' => 'Assistive device for learners with special education needs.',
                'manufacturer' => null,
                'description' => 'SPED learning support equipment.',
            ],
            [
                'item_name' => 'Sports Equipment Set',
                'category' => 'Sports',
                'brand' => null,
                'model' => null,
                'specifications' => 'Set of sports equipment for physical education activities.',
                'manufacturer' => null,
                'description' => 'Sports equipment used for PE classes and school athletics.',
            ],
        ];

        foreach ($items as $item) {
            OtherEquipmentCatalogItem::query()->updateOrCreate(
                [
                    'item_name' => $item['item_name'],
                    'category' => $item['category'],
                ],
                $item + ['is_active' => true],
            );
        }
    }
}
