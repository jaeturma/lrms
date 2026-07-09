<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\District;
use App\Models\Municipality;
use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    /**
     * Seed a central elementary school and a national high school for every
     * municipality, using stable SID numbers so re-runs never duplicate.
     */
    public function run(): void
    {
        if (School::withTrashed()->exists()) {
            $this->command?->warn('Schools already exist; skipping sample school seeding.');

            return;
        }

        $municipalities = Municipality::query()->orderBy('name')->get();

        if ($municipalities->isEmpty()) {
            $this->command?->warn('No municipalities found; run LocationSeeder first.');

            return;
        }

        foreach ($municipalities->values() as $index => $municipality) {
            $districts = District::query()
                ->where('municipality_id', $municipality->id)
                ->orderBy('name')
                ->get();

            if ($districts->isEmpty()) {
                continue;
            }

            $poblacion = Barangay::query()
                ->where('municipality_id', $municipality->id)
                ->where('name', 'like', '%Poblacion%')
                ->first();

            School::query()->updateOrCreate(
                ['school_id' => sprintf('SID-%05d', 30001 + ($index * 2))],
                [
                    'district_id' => $districts->first()->id,
                    'municipality_id' => $municipality->id,
                    'barangay_id' => $poblacion?->id,
                    'school_name' => "{$municipality->name} Central Elementary School",
                    'school_type' => 'Elementary',
                    'is_activated' => false,
                ],
            );

            School::query()->updateOrCreate(
                ['school_id' => sprintf('SID-%05d', 30002 + ($index * 2))],
                [
                    'district_id' => $districts->last()->id,
                    'municipality_id' => $municipality->id,
                    'barangay_id' => $poblacion?->id,
                    'school_name' => "{$municipality->name} National High School",
                    'school_type' => 'JHS and SHS',
                    'is_activated' => false,
                ],
            );
        }
    }
}
