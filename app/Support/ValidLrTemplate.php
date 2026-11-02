<?php

namespace App\Support;

/**
 * Fixed row layout of the VALID-LR monitoring tool (the "ES ONLY" and
 * "HS ONLY" sheets of VALID-LR_Monitoring_Tool.xlsx), plus the naming
 * rules used to match the reference workbooks against it.
 */
class ValidLrTemplate
{
    public const NON_MATATAG_GROUP = 'Non-MATATAG / Revised K-12 LR';

    public const SENIOR_HIGH_GROUP = 'SHS';

    /**
     * @var array<string, array<int, string>>
     */
    public const ELEMENTARY = [
        'Grade 1' => ['Good Manners and Right Conduct (GMRC)', 'Language', 'Makabansa', 'Mathematics', 'Reading and Literacy'],
        'Grade 2' => ['English', 'Filipino', 'Good Manners and Right Conduct (GMRC)', 'Makabansa', 'Mathematics'],
        'Grade 3' => ['English', 'Filipino', 'Good Manners and Right Conduct (GMRC)', 'Makabansa', 'Mathematics', 'Science'],
        'Grade 4' => ['Araling Panlipunan', 'Edukasyon Pantahanan at Pangkabuhayan', 'English', 'Filipino', 'Good Manners and Right Conduct (GMRC)', 'Mathematics', 'Music and Arts', 'Physical Education and Health', 'Science'],
        'Grade 5' => ['Araling Panlipunan', 'Edukasyon Pantahanan at Pangkabuhayan', 'English', 'Filipino', 'Good Manners and Right Conduct (GMRC)', 'Mathematics', 'Music and Arts', 'Physical Education and Health', 'Science'],
        'Grade 6' => ['Araling Panlipunan', 'Edukasyon Pantahanan at Pangkabuhayan', 'English', 'Filipino', 'Good Manners and Right Conduct (GMRC)', 'Mathematics', 'Music and Arts', 'Physical Education and Health', 'Science'],
        'Kindergarten' => ['Learning Packages', 'Activity Sheets', 'Manipulative Toys', 'Workbook 2026'],
    ];

    /**
     * @var array<string, array<int, string>>
     */
    public const SECONDARY = [
        'Grade 7' => ['Araling Panlipunan', 'English', 'Filipino', 'Mathematics', 'Music and Arts', 'Physical Education and Health', 'Science', 'Technology and Livelihood Education', 'Values Education'],
        'Grade 8' => ['Araling Panlipunan', 'English', 'Filipino', 'Mathematics', 'Music and Arts', 'Physical Education and Health', 'Science', 'Technology and Livelihood Education', 'Values Education'],
        'Grade 9' => ['Araling Panlipunan', 'English', 'Filipino', 'Mathematics', 'Music and Arts', 'Physical Education and Health', 'Science', 'Technology and Livelihood Education', 'Values Education'],
        'Grade 10' => ['Araling Panlipunan', 'English', 'Filipino', 'Mathematics', 'Music and Arts', 'Physical Education and Health', 'Science', 'Technology and Livelihood Education', 'Values Education'],
        self::SENIOR_HIGH_GROUP => ['Contemporary Philippine Arts from the Regions', 'Earth and Life Science', 'General Mathematics', 'Health and Optimizing PE 1&2', 'Health and Optimizing PE 3&4', 'Media and Information Literacy', 'Personal Development', 'Physical Science', 'Statistics and Probability', 'Understanding Culture, Society and Politics'],
        self::NON_MATATAG_GROUP => ['Ibong Adarna', 'Florante at Laura', 'Noli Me Tangere', 'El Filibusterismo'],
    ];

    /**
     * @var array<int, string>
     */
    public const LEARNERS_WITH_DISABILITIES = [
        '2025 DRF for the Procurement of Manipulative Materials',
        'Laptop/s (2022)',
        'Laptop/s (2023)',
        'Laptop/s (2024)',
        'Primers',
        'Braille Textbooks (subject/grade level)',
        'Braille Practice Paper (box)',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    public const ARAL = [
        'Learners' => ['Workbook', 'Writing/Coloring Materials', 'Decodables', 'Flashcards', 'Leveled Readers'],
        'Tutors' => ['Workbook', 'Writing/Coloring Materials', 'Decodables', 'Flashcards', 'Leveled Readers'],
        'Other LRs (specify)' => ['Other LRs'],
    ];

    /**
     * LRMIS learning-area spellings that differ from the monitoring tool.
     *
     * @var array<string, string>
     */
    public const LEARNING_AREA_ALIASES = [
        'Musics and Arts' => 'Music and Arts',
    ];

    /**
     * Splits an allocation-sheet column header ("Grade 4 Science",
     * "SHS Physical Science", "Florante at Laura") into its grade group and
     * learning area. Returns null for columns that are not allocations.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function parseAllocationHeader(string $header): ?array
    {
        $header = trim($header);

        foreach (self::SECONDARY[self::NON_MATATAG_GROUP] as $novel) {
            if ($header === $novel || str_ends_with($header, ' '.$novel)) {
                return [self::NON_MATATAG_GROUP, $novel];
            }
        }

        if (preg_match('/^(Grade \d{1,2}|Kindergarten|SHS) (.+)$/', $header, $matches) !== 1) {
            return null;
        }

        return [$matches[1], $matches[2]];
    }

    public static function normalizeLearningArea(string $learningArea): string
    {
        $learningArea = trim(preg_replace('/\s+/', ' ', $learningArea) ?? $learningArea);

        return self::LEARNING_AREA_ALIASES[$learningArea] ?? $learningArea;
    }

    /**
     * LRMIS records Senior High School as Grade 11 / Grade 12; the
     * monitoring tool groups both under "SHS".
     *
     * @return array<int, string>
     */
    public static function lrmisGradesFor(string $gradeGroup): array
    {
        return $gradeGroup === self::SENIOR_HIGH_GROUP ? ['Grade 11', 'Grade 12'] : [$gradeGroup];
    }

    /**
     * LRMS grade_levels names whose enrolment counts toward a grade group.
     *
     * @return array<int, string>
     */
    public static function enrolmentGradesFor(string $gradeGroup): array
    {
        return match ($gradeGroup) {
            self::SENIOR_HIGH_GROUP => ['Grade 11', 'Grade 12'],
            self::NON_MATATAG_GROUP => [],
            default => [$gradeGroup],
        };
    }
}
