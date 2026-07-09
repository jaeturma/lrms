<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use App\Models\LearningResourceType;
use App\Models\School;
use App\Services\LearningResourceInventoryService;
use Illuminate\Database\Seeder;

class SampleLearningResourceSeeder extends Seeder
{
    /**
     * Seed a fully catalogued sample print resource against the first school.
     */
    public function run(): void
    {
        $school = School::query()->orderBy('id')->first();

        if (! $school) {
            $this->command?->warn('No schools found; skipping sample learning resource.');

            return;
        }

        $type = LearningResourceType::query()->updateOrCreate(
            ['name' => "Teacher's Manuals (TM)"],
            ['category' => 'Print', 'is_active' => true],
        );

        $gradeLevel = GradeLevel::query()->updateOrCreate(
            ['name' => 'Grade 7'],
            ['is_active' => true],
        );

        $resource = $school->learningResources()->updateOrCreate(
            ['isbn' => '978-971-94761-9-6'],
            [
                'learning_resource_type_id' => $type->id,
                'grade_level_id' => $gradeLevel->id,
                'title' => 'Edukasyon Sa Pagpapahalaga: Mga Pagpapahalaga Tungo Sa Pagtupad Ng Tungkulin',
                'author' => 'Vanessa M. Espiritu',
                'publisher' => 'Acfa Enterprises',
                'language' => 'Tagalog',
                'subject' => 'Values Education',
                'volume' => null,
                'edition' => null,
                'copyright_year' => 2024,
                'pages' => 194,
                'attachment_path' => 'learning-resources/attachments/esp-grade7-tm.pdf',
                'cover_image_path' => 'learning-resources/covers/esp-grade7-tm.jpg',
                'quantity_delivered' => 10,
                'quantity_with_issue_defect' => 0,
            ],
        );

        if (! $resource->inventory) {
            app(LearningResourceInventoryService::class)->initialize($resource);
        }
    }
}
