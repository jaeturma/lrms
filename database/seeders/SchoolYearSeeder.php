<?php

namespace Database\Seeders;

use App\Models\SchoolYear;
use Illuminate\Database\Seeder;

class SchoolYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([2024, 2025, 2026] as $startYear) {
            SchoolYear::query()->updateOrCreate(
                ['name' => $startYear.'-'.($startYear + 1)],
                [
                    'starts_on' => $startYear.'-06-01',
                    'ends_on' => ($startYear + 1).'-04-30',
                ],
            );
        }

        if (! SchoolYear::query()->where('is_active', true)->exists()) {
            SchoolYear::query()->where('name', '2026-2027')->update(['is_active' => true]);
        }
    }
}
