<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\District;
use App\Models\Municipality;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Municipalities of the Davao de Oro division with their school
     * districts and a representative set of barangays. The authoritative
     * barangay list can still be imported through the locations CSV import.
     *
     * @var array<string, array{districts: array<int, string>, barangays: array<int, string>}>
     */
    private const LOCATIONS = [
        'Compostela' => [
            'districts' => ['Compostela I', 'Compostela II'],
            'barangays' => ['Poblacion', 'Ngan', 'Osmeña', 'San Miguel', 'Mangayon'],
        ],
        'Laak' => [
            'districts' => ['Laak I', 'Laak II'],
            'barangays' => ['Laac (Poblacion)', 'Aguinaldo', 'Kapatagan', 'Concepcion'],
        ],
        'Mabini' => [
            'districts' => ['Mabini'],
            'barangays' => ['Cuambog (Poblacion)', 'Pindasan', 'Cadunan', 'Tagnanan'],
        ],
        'Maco' => [
            'districts' => ['Maco I', 'Maco II'],
            'barangays' => ['Poblacion', 'Binuangan', 'Elizalde', 'Kinuban', 'Mainit'],
        ],
        'Maragusan' => [
            'districts' => ['Maragusan I', 'Maragusan II'],
            'barangays' => ['Poblacion', 'Bahi', 'Katipunan', 'New Albay'],
        ],
        'Mawab' => [
            'districts' => ['Mawab'],
            'barangays' => ['Poblacion', 'Andili', 'Bawani', 'Concepcion'],
        ],
        'Monkayo' => [
            'districts' => ['Monkayo I', 'Monkayo II'],
            'barangays' => ['Poblacion', 'Awao', 'Babag', 'Mount Diwata', 'Upper Ulip'],
        ],
        'Montevista' => [
            'districts' => ['Montevista'],
            'barangays' => ['San Jose (Poblacion)', 'Camansi', 'Dauman', 'New Visayas'],
        ],
        'Nabunturan' => [
            'districts' => ['Nabunturan I', 'Nabunturan II'],
            'barangays' => ['Poblacion', 'Anislagan', 'Cabacungan', 'Katipunan', 'Magading'],
        ],
        'New Bataan' => [
            'districts' => ['New Bataan I', 'New Bataan II'],
            'barangays' => ['Cabinuangan (Poblacion)', 'Andap', 'Camanlangan', 'Magsaysay'],
        ],
        'Pantukan' => [
            'districts' => ['Pantukan I', 'Pantukan II'],
            'barangays' => ['Kingking (Poblacion)', 'Magnaga', 'Bongabong', 'Tagdangua'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Municipality::query()->exists()) {
            $this->command?->warn('Municipalities already exist; skipping location seeding.');

            return;
        }

        foreach (self::LOCATIONS as $municipalityName => $location) {
            $municipality = Municipality::query()->updateOrCreate(['name' => $municipalityName]);

            foreach ($location['districts'] as $districtName) {
                District::query()->updateOrCreate([
                    'municipality_id' => $municipality->id,
                    'name' => $districtName,
                ]);
            }

            foreach ($location['barangays'] as $barangayName) {
                Barangay::query()->updateOrCreate([
                    'municipality_id' => $municipality->id,
                    'name' => $barangayName,
                ]);
            }
        }
    }
}
