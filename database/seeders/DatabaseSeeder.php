<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            RoleUserSeeder::class,
            LearningResourceTypeSeeder::class,
            DigitalLearningMaterialSeeder::class,
            IctEquipmentCatalogSeeder::class,
            OtherEquipmentCatalogSeeder::class,
            SmeCatalogSeeder::class,
            GradeLevelSeeder::class,
            SchoolYearSeeder::class,
            LocationSeeder::class,
            SchoolSeeder::class,
        ]);
    }
}
