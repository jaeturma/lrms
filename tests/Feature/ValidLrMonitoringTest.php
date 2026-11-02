<?php

use App\Models\District;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\LearningResource;
use App\Models\Municipality;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\User;
use App\Models\ValidLrSchool;
use App\Models\ValidLrSmeItem;
use App\Models\ValidLrTitle;
use App\Models\ValidLrTvlItem;
use App\Models\ValidLrVisit;
use App\Models\ValidLrVisitItem;
use App\Services\ValidLrReferenceImportService;
use App\Services\ValidLrVisitService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Builds a minimal .xlsx workbook (inline strings) with the given sheets.
 *
 * @param  array<string, array<int, array<int, string|int|null>>>  $sheets
 */
function validLrWorkbook(array $sheets): string
{
    $path = tempnam(sys_get_temp_dir(), 'validlr').'.xlsx';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $sheetEntries = '';
    $relationships = '';
    $index = 0;

    foreach ($sheets as $name => $rows) {
        $index++;
        $sheetEntries .= '<sheet name="'.htmlspecialchars($name).'" sheetId="'.$index.'" r:id="rId'.$index.'"/>';
        $relationships .= '<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$index.'.xml"/>';

        $rowsXml = '';

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 1;
            $cells = '';

            foreach (array_values($row) as $columnIndex => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $reference = validLrColumnLetter($columnIndex).$rowNumber;
                $cells .= is_int($value)
                    ? '<c r="'.$reference.'"><v>'.$value.'</v></c>'
                    : '<c r="'.$reference.'" t="inlineStr"><is><t>'.htmlspecialchars($value).'</t></is></c>';
            }

            $rowsXml .= '<row r="'.$rowNumber.'">'.$cells.'</row>';
        }

        $zip->addFromString(
            "xl/worksheets/sheet{$index}.xml",
            '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$rowsXml.'</sheetData></worksheet>',
        );
    }

    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'.$sheetEntries.'</sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$relationships.'</Relationships>');
    $zip->close();

    return $path;
}

function validLrColumnLetter(int $index): string
{
    $letters = '';

    for ($index++; $index > 0; $index = intdiv($index - 1, 26)) {
        $letters = chr(65 + (($index - 1) % 26)).$letters;
    }

    return $letters;
}

function validLrReferenceWorkbook(): string
{
    $profile = ['#EMISD', 'Region', 'Division', 'District', 'beis_school_id', 'School Visited', 'street_address', 'Mother School ID', 'Province', 'Municipality', 'Leg_district', 'Barangay', 'Sector', 'School_type', 'Modified Curricular Offering Classification'];

    return validLrWorkbook([
        'allocation' => [
            [...$profile, 'Grade 1 Language', 'Grade 7 Science', 'Grade 7 Values Education', 'SHS Physical Science', 'Florante at Laura'],
            ['1', 'Region XI', 'Davao De Oro', 'Nabunturan', '304205', 'Nabunturan NCHS', 'Poblacion', '', 'DAVAO DE ORO', 'NABUNTURAN', '1st', 'POBLACION', 'Public', 'School with no Annexes', 'JHS with SHS', 18955, 832, 806, 120, 40],
            ['2', 'Region XI', 'Davao del Norte', 'Tagum', '999001', 'Outside School', '', '', '', '', '', '', 'Public', '', 'Purely ES', '', 10, 10, 10, 10],
        ],
        'LRD_Elem' => [
            ['Grade', 'TITLE', 'Region XI', 'Region XII', '', 'Region XI', 'Region XII'],
            ['Grade 1', 'Language', 'Wika', 'Wika', null, 'Vibal', 'Vibal'],
        ],
        'LRD_HS' => [
            ['Grade', 'TITLE', 'Region XI', 'Region XII', '', 'Region XI', 'Region XII'],
            ['Grade 7', 'Science', 'Science in the Modern World', 'Other Science', null, 'SIBS', 'Rex'],
            ['Grade 7', 'Values Education', 'EDUKASYON SA PAGPAPAHALAGA:', null, null, 'ACFA', null],
            [null, null, 'Mga Pagpapahalaga', null, null, null, null],
        ],
    ]);
}

function importValidLr(string $type, string $path, ?string $division = null): array
{
    return app(ValidLrReferenceImportService::class)->import($type, $path, $division);
}

function seedValidLrReferenceData(): ValidLrSchool
{
    importValidLr('reference_data', validLrReferenceWorkbook(), 'davao de oro');

    importValidLr('lrmis_encoded', validLrWorkbook([
        'LRMIS_ENCODED' => [
            ['grade_level', 'subject_name', 'total_lr', 'station_name', 'beis_id'],
            ['Grade 7', 'Science', '832.0', 'Nabunturan NCHS', '304205.0'],
            ['Grade 11', 'Physical Science', '50.0', 'Nabunturan NCHS', '304205.0'],
            ['Grade 12', 'Physical Science', '30.0', 'Nabunturan NCHS', '304205.0'],
            ['Grade 7', 'Science', '5.0', 'Outside School', '999001.0'],
        ],
    ]));

    importValidLr('sme_masterlist', validLrWorkbook([
        'SME' => [['Region', 'Division']],
        'Sheet1' => [
            ['BEIS School ID', 'School Name', 'SME 2012-Junior High School', 'SME 2018-Junior High School', 'SME 2016-Senior High School', 'SME 2014-Grade 1 to Grade 3'],
            ['304205', 'Nabunturan NCHS', 1, 1, 1, 0],
        ],
    ]));

    importValidLr('monitoring_tool', validLrWorkbook([
        'SME CO Allocation' => [
            ['LOT Description', 'Item Name', 'Unit', 'Key Stage', 'Quantity'],
            ['LOT 1', 'Stand Rod', 'pc', 'Junior High School', 5],
            ['LOT 1', 'Blackboard Protractor', 'pc', 'Grade 1 to Grade 3', 1],
            ['LOT 1', 'Zero Quantity Item', 'pc', 'Junior High School', 0],
        ],
        'SME DL Allocation' => [
            ['Learning Area', 'LOT Description / Item Name', 'Unit', 'Key Stage', 'Quantity'],
            ['Biology', 'Glass Slides', 'box', 'Senior High School', 10],
        ],
        'TVL' => [
            ['School ID:'],
            ['Item Name', 'Central Office Procured', 'RO/SDO Procurement (Downloaded Funds)', 'Program/Specialization', 'Qty per Specialization'],
            ['Bolo', null, 1, 'Agricultural Crops Production NC I', 5],
            ['Oven', 1, null, 'Bread and Pastry Production NC II', 1],
        ],
        'TVL Procurement Status' => [
            ['BEIS School ID', 'School Name', 'Program Offering/Specialization', '2018', '2019', 'STATUS'],
            ['304205', 'Nabunturan NCHS', 'Agricultural Crops Production NC I', 0, 1, 'Delivered'],
            ['999001', 'Outside School', 'Bread and Pastry Production NC II', 1, 0, 'Delivered'],
        ],
    ]));

    return ValidLrSchool::query()->where('beis_school_id', '304205')->sole();
}

/**
 * An LRMS school account for BEIS 304205 with a complete VALID-LR profile and
 * current-year enrolment.
 *
 * @return array{school: School, user: User}
 */
function validLrSchoolAccount(bool $completeProfile = true): array
{
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);
    $school = School::factory()->create([
        'school_id' => '304205',
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
        'email' => 'nchs@example.test',
        'school_head' => 'Head Teacher',
        'lr_designate' => $completeProfile ? 'LR Designate' : null,
        'ict_coordinator' => $completeProfile ? 'ICT Coordinator' : null,
        'math_coordinator' => $completeProfile ? 'Math Coordinator' : null,
        'science_coordinator' => $completeProfile ? 'Science Coordinator' : null,
        'property_custodian' => $completeProfile ? 'Property Custodian' : null,
    ]);
    $user = User::factory()->create(['role' => 'school', 'school_id' => $school->id]);

    return ['school' => $school, 'user' => $user];
}

function encodeValidLrEnrolment(School $school): void
{
    Enrollment::query()->create([
        'school_id' => $school->id,
        'school_year_id' => SchoolYear::factory()->create(['is_active' => true])->id,
        'grade_level_id' => GradeLevel::factory()->create(['name' => 'Grade 7'])->id,
        'male_count' => 400,
        'female_count' => 410,
    ]);
}

/**
 * @return array<int, array{id: int, school_quantity: int}>
 */
function countEveryValidLrItem(ValidLrVisit $visit, int $quantity = 5): array
{
    return $visit->items()
        ->whereIn('section', ValidLrVisit::COUNTED_SECTIONS)
        ->pluck('id')
        ->map(fn (int $id): array => ['id' => $id, 'school_quantity' => $quantity])
        ->all();
}

test('reference data import keeps only the chosen division and joins wrapped titles', function () {
    $summary = importValidLr('reference_data', validLrReferenceWorkbook(), 'davao de oro');

    expect($summary)->toMatchArray([
        'division' => 'Davao De Oro',
        'schools_created' => 1,
        'rows_outside_division' => 1,
        'allocations' => 5,
    ]);

    $school = ValidLrSchool::query()->sole();

    expect($school->beis_school_id)->toBe('304205');
    expect($school->curricular_offering)->toBe('JHS with SHS');
    expect($school->allocations()->pluck('quantity', 'learning_area')->all())->toMatchArray([
        'Language' => 18955,
        'Science' => 832,
        'Values Education' => 806,
        'Physical Science' => 120,
        'Florante at Laura' => 40,
    ]);

    $values = ValidLrTitle::query()->where('region', 'Region XI')->where('learning_area', 'Values Education')->sole();

    expect($values->title)->toBe('EDUKASYON SA PAGPAPAHALAGA: Mga Pagpapahalaga');
    expect($values->publisher)->toBe('ACFA');
});

test('reference data import rejects a division that is not in the file', function () {
    importValidLr('reference_data', validLrReferenceWorkbook(), 'Unknown Division');
})->throws(ValidationException::class);

test('per-school workbooks require the reference data first', function () {
    importValidLr('lrmis_encoded', validLrWorkbook([
        'LRMIS_ENCODED' => [['grade_level', 'subject_name', 'total_lr', 'beis_id']],
    ]));
})->throws(ValidationException::class);

test('lrmis, sme and tvl imports only keep schools in the division', function () {
    $school = seedValidLrReferenceData();

    expect($school->lrmisCounts()->count())->toBe(3);
    expect($school->sme_deliveries)->toBe([
        ['year' => 2012, 'key_stage' => 'Junior High School'],
        ['year' => 2018, 'key_stage' => 'Junior High School'],
        ['year' => 2016, 'key_stage' => 'Senior High School'],
    ]);
    expect(ValidLrSmeItem::query()->count())->toBe(4);
    expect(ValidLrTvlItem::query()->where('item_name', 'Bolo')->sole()->procurement_source)->toBe(ValidLrSmeItem::SOURCE_DOWNLOADED);
    expect($school->tvlPrograms()->sole()->delivery_years)->toBe([2019]);
});

test('school lookup mirrors the monitoring tool for a school id', function () {
    $school = seedValidLrReferenceData();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.valid-lr.schools.show', $school))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminValidLrSchool')
            ->where('school.beis_school_id', '304205')
            ->where('school.lrms_school', null)
            ->where('profile.learning_resources', fn ($rows) => collect($rows)->contains(fn (array $row): bool => $row['group_label'] === 'Grade 7'
                && $row['label'] === 'Science'
                && $row['title'] === 'Science in the Modern World'
                && $row['publisher'] === 'SIBS'
                && $row['allocated'] === 832
                && $row['lrmis'] === 832)
                && collect($rows)->contains(fn (array $row): bool => $row['group_label'] === 'SHS'
                    && $row['label'] === 'Physical Science'
                    && $row['allocated'] === 120
                    && $row['lrmis'] === 80)
                && collect($rows)->every(fn (array $row): bool => $row['level'] === 'secondary'))
            ->where('profile.sme.timeline.Junior High School', [2012, 2018])
            ->where('profile.sme.timeline.Grade 1 to Grade 3', [])
            ->has('profile.sme.central_office', 1)
            ->where('profile.sme.central_office.0.item_name', 'Stand Rod')
            ->has('profile.sme.downloaded', 1)
            ->has('profile.tvl.items', 1)
            ->where('profile.tvl.items.0.item_name', 'Bolo'));
});

test('index lookup redirects to an exact school id match', function () {
    $school = seedValidLrReferenceData();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.valid-lr.index', ['search' => '304205', 'lookup' => 1]))
        ->assertRedirect(route('admin.valid-lr.schools.show', $school));

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.valid-lr.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminValidLr')
            ->where('stats.schools', 1)
            ->where('canImport', true)
            ->has('schools.data', 1));
});

test('lookup adds lrms recorded quantities and enrolment for schools registered in lrms', function () {
    $validLrSchool = seedValidLrReferenceData();
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);
    $school = School::factory()->create([
        'school_id' => '304205',
        'district_id' => $district->id,
        'municipality_id' => $municipality->id,
    ]);
    $grade7 = GradeLevel::factory()->create(['name' => 'Grade 7']);
    $schoolYear = SchoolYear::factory()->create(['is_active' => true]);

    LearningResource::factory()->create([
        'school_id' => $school->id,
        'grade_level_id' => $grade7->id,
        'subject' => 'Science',
        'quantity_delivered' => 700,
    ]);
    Enrollment::query()->create([
        'school_id' => $school->id,
        'school_year_id' => $schoolYear->id,
        'grade_level_id' => $grade7->id,
        'male_count' => 400,
        'female_count' => 410,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.valid-lr.schools.show', $validLrSchool))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('school.lrms_school.school_id', '304205')
            ->where('profile.learning_resources', fn ($rows) => collect($rows)->contains(fn (array $row): bool => $row['group_label'] === 'Grade 7'
                && $row['label'] === 'Science'
                && $row['lrms'] === 700
                && $row['enrolment'] === 810)));
});

test('starting a visit snapshots the reference data into editable items', function () {
    $school = seedValidLrReferenceData();
    $admin = User::factory()->admin()->create(['name' => 'Monitor One']);

    $response = $this->actingAs($admin)->post(route('admin.valid-lr.visits.store', $school));

    $visit = ValidLrVisit::query()->sole();
    $response->assertRedirect(route('admin.valid-lr.visits.edit', $visit));

    expect($visit->user_id)->toBe($admin->id);
    expect($visit->prepared_by)->toBe('Monitor One');
    expect($visit->items()->where('section', ValidLrVisitItem::SECTION_SME_CENTRAL_OFFICE)->sole()->year_received)->toBe('2012, 2018');
    expect($visit->items()->where('section', ValidLrVisitItem::SECTION_TVL)->sole()->label)->toBe('Bolo');
    expect($visit->items()->where('section', ValidLrVisitItem::SECTION_ARAL)->count())->toBe(11);

    $science = $visit->items()->where('section', ValidLrVisitItem::SECTION_LEARNING_RESOURCES)
        ->where('group_label', 'Grade 7')->where('label', 'Science')->sole();

    expect($science->reference_quantity)->toBe(832);
    expect($science->lrmis_quantity)->toBe(832);

    // A re-import must not change what the visit already captured.
    ValidLrSmeItem::query()->delete();
    expect($visit->items()->where('section', ValidLrVisitItem::SECTION_SME_CENTRAL_OFFICE)->count())->toBe(1);

    $this->actingAs($admin)
        ->get(route('admin.valid-lr.visits.edit', $visit))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminValidLrVisit')
            ->where('visit.id', $visit->id)
            ->has('indicatorQuestions', 4));
});

test('saving a visit records monitor inputs but never overwrites the imported reference values', function () {
    $school = seedValidLrReferenceData();
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->post(route('admin.valid-lr.visits.store', $school));
    $visit = ValidLrVisit::query()->sole();
    $visit->update(['school_submitted_at' => now()]);

    $science = $visit->items()->where('section', 'lr')->where('group_label', 'Grade 7')->where('label', 'Science')->sole();
    $laptops = $visit->items()->where('section', 'im_lwd')->where('label', 'Laptop/s (2022)')->sole();
    $workbook = $visit->items()->where('section', 'aral')->where('group_label', 'Learners')->where('label', 'Workbook')->sole();

    $this->actingAs($admin)
        ->put(route('admin.valid-lr.visits.update', $visit), [
            'status' => 'completed',
            'visited_at' => '2026-11-02 09:30:00',
            'monitoring_team' => 'Team A',
            'school_representative' => 'School Head',
            'prepared_by' => 'Monitor One',
            'indicators' => [
                'lrmis_account' => ['answer' => 'yes', 'remarks' => 'Active'],
                'unknown_question' => ['answer' => 'no'],
            ],
            'lr_delivery_status' => 'Incomplete',
            'key_findings' => 'Grade 7 Science short by 2 copies.',
            'items' => [
                [
                    'id' => $science->id,
                    'reference_quantity' => 1,
                    'actual_quantity' => 830,
                    'distributed_quantity' => 832,
                    'mov' => ['Delivery Receipts', 'Photo Documentation'],
                ],
                ['id' => $laptops->id, 'reference_quantity' => 3, 'actual_quantity' => 2, 'remarks_others' => 'One unit missing'],
                ['id' => $workbook->id, 'extra' => ['aral_reading_2025' => 25, 'not_a_program' => 9]],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $visit->refresh();
    $science->refresh();
    $laptops->refresh();
    $workbook->refresh();

    expect($visit->status)->toBe('completed');
    expect($visit->indicators)->toBe(['lrmis_account' => ['answer' => 'yes', 'remarks' => 'Active']]);
    expect($visit->lr_delivery_status)->toBe('Incomplete');
    expect($science->reference_quantity)->toBe(832);
    expect($science->actual_quantity)->toBe(830);
    expect($science->mov)->toBe(['Delivery Receipts', 'Photo Documentation']);
    expect($laptops->reference_quantity)->toBe(3);
    expect($laptops->actual_quantity)->toBe(2);
    expect($workbook->extra)->toBe(['aral_reading_2025' => 25]);
});

test('visit update validates mov options and delivery statuses', function () {
    $school = seedValidLrReferenceData();
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->post(route('admin.valid-lr.visits.store', $school));
    $visit = ValidLrVisit::query()->sole();
    $item = $visit->items()->first();

    $this->actingAs($admin)
        ->put(route('admin.valid-lr.visits.update', $visit), [
            'status' => 'archived',
            'lr_delivery_status' => 'Maybe',
            'items' => [['id' => $item->id, 'mov' => ['Handwritten note'], 'actual_quantity' => -1]],
        ])
        ->assertSessionHasErrors(['status', 'lr_delivery_status', 'items.0.mov.0', 'items.0.actual_quantity']);
});

test('a visit cannot be marked completed until its key details are filled in', function () {
    $school = seedValidLrReferenceData();
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->post(route('admin.valid-lr.visits.store', $school));
    $visit = ValidLrVisit::query()->sole();
    $visit->update(['school_submitted_at' => now()]);

    $this->actingAs($admin)
        ->put(route('admin.valid-lr.visits.update', $visit), [
            'status' => 'completed',
            'visited_at' => null,
            'monitoring_team' => '',
            'school_representative' => '',
            'prepared_by' => '',
        ])
        ->assertSessionHasErrors(['visited_at', 'monitoring_team', 'school_representative', 'prepared_by']);

    expect($visit->refresh()->status)->toBe('draft');

    // A draft can still be saved with those details missing.
    $this->actingAs($admin)
        ->put(route('admin.valid-lr.visits.update', $visit), [
            'status' => 'draft',
            'monitoring_team' => '',
        ])
        ->assertSessionHasNoErrors();
});

test('a visit has a printable copy', function () {
    $school = seedValidLrReferenceData();
    $admin = User::factory()->admin()->create(['name' => 'Printer User']);
    $this->actingAs($admin)->post(route('admin.valid-lr.visits.store', $school));
    $visit = ValidLrVisit::query()->sole();

    $this->actingAs($admin)
        ->get(route('admin.valid-lr.visits.print', $visit))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminValidLrVisitPrint')
            ->where('visit.id', $visit->id)
            ->where('school.beis_school_id', '304205')
            ->where('generatedBy', 'Printer User')
            ->has('visit.items', $visit->items()->count())
            ->has('indicatorQuestions', 4));
});

test('monitoring report rolls up coverage, indicators, delivery status and discrepancies', function () {
    $school = seedValidLrReferenceData();
    ValidLrSchool::factory()->create(['district' => $school->district]);
    ValidLrSchool::factory()->create(['district' => 'Other District']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.valid-lr.visits.store', $school));
    $visit = ValidLrVisit::query()->sole();
    $visit->update(['school_submitted_at' => now()]);
    $science = $visit->items()->where('section', 'lr')->where('group_label', 'Grade 7')->where('label', 'Science')->sole();
    $standRod = $visit->items()->where('section', 'sme_co')->sole();

    $this->actingAs($admin)->put(route('admin.valid-lr.visits.update', $visit), [
        'status' => 'completed',
        'visited_at' => '2026-11-02 09:30:00',
        'monitoring_team' => 'Team A',
        'school_representative' => 'School Head',
        'prepared_by' => 'Monitor One',
        'indicators' => ['lrmis_account' => ['answer' => 'yes'], 'inventory_matches' => ['answer' => 'no']],
        'lr_delivery_status' => 'Incomplete',
        'sme_delivery_status' => 'Complete',
        'items' => [
            ['id' => $science->id, 'actual_quantity' => 830],
            ['id' => $standRod->id, 'actual_quantity' => 5],
        ],
    ])->assertSessionHasNoErrors();

    ValidLrVisit::factory()->create(['visited_at' => '2026-10-01 08:00:00']);

    $this->actingAs($admin)
        ->get(route('admin.valid-lr.report', ['district' => $school->district]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminValidLrReport')
            ->where('report.summary.schools', 2)
            ->where('report.summary.schools_monitored', 1)
            ->where('report.summary.completed', 1)
            ->where('report.summary.coverage', 50)
            ->where('report.districts.0.district', $school->district)
            ->where('report.indicators.0.yes', 1)
            ->where('report.indicators.2.no', 1)
            ->where('report.delivery.0.incomplete', 1)
            ->where('report.delivery.1.complete', 1)
            ->has('report.visits', 1)
            ->where('report.visits.0.items_counted', 2)
            ->where('report.visits.0.discrepancies', 1));

    $this->actingAs($admin)
        ->get(route('admin.valid-lr.report', ['status' => 'draft']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('report.visits', 1)
            ->where('report.visits.0.status', 'draft'));

    $this->actingAs($admin)
        ->get(route('admin.valid-lr.report', ['from' => '2026-11-01', 'to' => '2026-11-30']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('report.visits', 1)
            ->where('report.visits.0.id', $visit->id));
});

test('monitoring report can be exported to csv and printed', function () {
    $school = seedValidLrReferenceData();
    $admin = User::factory()->admin()->create();
    ValidLrVisit::factory()->for($school, 'validLrSchool')->create([
        'status' => 'completed',
        'monitoring_team' => 'Team CSV',
        'key_findings' => 'All accounted for.',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.valid-lr.report.export'));
    $response->assertOk()->assertDownload('valid-lr-monitoring-report.csv');

    $csv = $response->streamedContent();
    expect($csv)->toContain('"School ID",School,District')
        ->and($csv)->toContain('304205')
        ->and($csv)->toContain('Team CSV')
        ->and($csv)->toContain('All accounted for.');

    $this->actingAs($admin)
        ->get(route('admin.valid-lr.report.print', ['status' => 'completed']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('AdminValidLrReportPrint')
            ->where('filters.status', 'completed')
            ->has('report.visits', 1));
});

test('visits can be deleted', function () {
    $school = seedValidLrReferenceData();
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->post(route('admin.valid-lr.visits.store', $school));
    $visit = ValidLrVisit::query()->sole();

    $this->actingAs($admin)
        ->delete(route('admin.valid-lr.visits.destroy', $visit))
        ->assertRedirect(route('admin.valid-lr.schools.show', $school));

    expect(ValidLrVisit::query()->count())->toBe(0);
    expect(ValidLrVisitItem::query()->count())->toBe(0);
});

test('only system admins can upload reference workbooks', function () {
    $file = new UploadedFile(validLrReferenceWorkbook(), '_reference_data.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $this->actingAs(User::factory()->create(['role' => 'librarian']))
        ->post(route('admin.valid-lr.import.store'), ['type' => 'reference_data', 'division' => 'Davao De Oro', 'file' => $file])
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.valid-lr.import.store'), ['type' => 'reference_data', 'division' => 'Davao De Oro', 'file' => $file])
        ->assertOk()
        ->assertJsonPath('summary.schools_created', 1);
});

test('reference data upload requires a division', function () {
    $file = new UploadedFile(validLrReferenceWorkbook(), '_reference_data.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $this->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.valid-lr.import.store'), ['type' => 'reference_data', 'file' => $file])
        ->assertJsonValidationErrors('division');
});

test('school users cannot reach valid-lr monitoring', function () {
    $this->actingAs(User::factory()->create(['role' => 'school']))
        ->get(route('admin.valid-lr.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create(['role' => 'school']))
        ->get(route('admin.valid-lr.report'))
        ->assertForbidden();
});

test('a school prepares its own counts and can submit only after completing the checklist', function () {
    seedValidLrReferenceData();
    ['school' => $school, 'user' => $user] = validLrSchoolAccount(completeProfile: false);

    $this->actingAs($user)
        ->get(route('school.valid-lr.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolValidLr')
            ->where('available', true)
            ->where('canEdit', true)
            ->where('visit.stage', 'awaiting_school')
            ->where('checklist.ready', false)
            ->where('checklist.missing_profile', ['LR Designate', 'ICT Coordinator', 'Math Coordinator', 'Science Coordinator', 'Property Custodian']));

    $visit = ValidLrVisit::query()->sole();
    expect($visit->user_id)->toBeNull();
    expect($visit->school_representative)->toBe('Head Teacher');

    $science = $visit->items()->where('section', 'lr')->where('group_label', 'Grade 7')->where('label', 'Science')->sole();

    $this->actingAs($user)
        ->put(route('school.valid-lr.update'), [
            'school_representative' => 'Head Teacher',
            'items' => [['id' => $science->id, 'school_quantity' => 820, 'actual_quantity' => 1, 'mov' => ['Delivery Receipts']]],
        ])
        ->assertSessionHasNoErrors();

    $science->refresh();
    expect($science->school_quantity)->toBe(820);
    expect($science->actual_quantity)->toBeNull();
    expect($science->mov)->toBe(['Delivery Receipts']);

    $this->actingAs($user)
        ->post(route('school.valid-lr.submit'))
        ->assertSessionHasErrors('submit');
    expect($visit->refresh()->school_submitted_at)->toBeNull();

    $school->update([
        'lr_designate' => 'Ana Cruz',
        'ict_coordinator' => 'Ben Reyes',
        'math_coordinator' => 'Carla Diaz',
        'science_coordinator' => 'Dan Lim',
        'property_custodian' => 'Eva Tan',
    ]);
    encodeValidLrEnrolment($school);
    $this->actingAs($user)->put(route('school.valid-lr.update'), ['items' => countEveryValidLrItem($visit)]);

    $this->actingAs($user)
        ->post(route('school.valid-lr.submit'))
        ->assertSessionHasNoErrors();

    $visit->refresh();
    expect($visit->school_submitted_at)->not->toBeNull();
    expect($visit->school_submitted_by)->toBe($user->id);
    expect($visit->stage())->toBe(ValidLrVisit::STAGE_READY);

    // The school can still correct its counts after submitting.
    $this->actingAs($user)
        ->put(route('school.valid-lr.update'), ['items' => [['id' => $science->id, 'school_quantity' => 830]]])
        ->assertSessionHasNoErrors();
    expect($science->refresh()->school_quantity)->toBe(830);
});

test('the monitoring team cannot validate before the school submits unless an admin overrides', function () {
    $validLrSchool = seedValidLrReferenceData();
    $leader = User::factory()->create(['role' => 'librarian', 'name' => 'Team Leader']);
    $manager = User::factory()->create(['role' => 'manager']);

    $this->actingAs($manager)
        ->post(route('admin.valid-lr.visits.store', $validLrSchool), [
            'team_leader_id' => $leader->id,
            'scheduled_at' => '2026-11-10 08:00:00',
            'monitoring_team' => 'Team A',
        ])
        ->assertRedirect();

    $visit = ValidLrVisit::query()->sole();
    expect($visit->team_leader_id)->toBe($leader->id);
    expect($visit->scheduled_at->toDateString())->toBe('2026-11-10');

    $this->actingAs($leader)
        ->put(route('admin.valid-lr.visits.update', $visit), ['status' => 'draft', 'monitoring_team' => 'Team A'])
        ->assertSessionHasErrors('status');
    expect($visit->refresh()->validation_started_at)->toBeNull();

    $this->actingAs($leader)
        ->post(route('admin.valid-lr.visits.override', $visit), ['override_reason' => 'School has no LRMS account yet.'])
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.valid-lr.visits.override', $visit), ['override_reason' => 'School has no LRMS account yet.'])
        ->assertSessionHasNoErrors();

    expect($visit->refresh()->stage())->toBe(ValidLrVisit::STAGE_READY);

    $this->actingAs($leader)
        ->put(route('admin.valid-lr.visits.update', $visit), ['status' => 'draft', 'monitoring_team' => 'Team A'])
        ->assertSessionHasNoErrors();

    expect($visit->refresh()->stage())->toBe(ValidLrVisit::STAGE_VALIDATING);
});

test('only the assigned team leader and admins can edit a visit; other monitoring roles only watch', function () {
    $validLrSchool = seedValidLrReferenceData();
    $leader = User::factory()->create(['role' => 'librarian']);
    $visit = app(ValidLrVisitService::class)->start($validLrSchool, null, ['team_leader_id' => $leader->id]);
    $visit->update(['school_submitted_at' => now()]);

    foreach (['cidchief', 'sds', 'manager', 'librarian'] as $role) {
        $viewer = User::factory()->create(['role' => $role]);

        $this->actingAs($viewer)
            ->get(route('admin.valid-lr.visits.edit', $visit))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('canEdit', false)->where('canDelete', false));

        $this->actingAs($viewer)
            ->put(route('admin.valid-lr.visits.update', $visit), ['status' => 'draft'])
            ->assertForbidden();
    }

    $this->actingAs($leader)
        ->get(route('admin.valid-lr.visits.edit', $visit))
        ->assertInertia(fn (Assert $page) => $page->where('canEdit', true));

    $this->actingAs(User::factory()->create(['role' => 'librarian']))
        ->post(route('admin.valid-lr.visits.store', $validLrSchool))
        ->assertForbidden();

    $this->actingAs($leader)
        ->delete(route('admin.valid-lr.visits.destroy', $visit))
        ->assertForbidden();
});

test('scheduling reuses the visit the school already started', function () {
    $validLrSchool = seedValidLrReferenceData();
    ['user' => $user] = validLrSchoolAccount();
    $this->actingAs($user)->get(route('school.valid-lr.index'));
    $leader = User::factory()->create(['role' => 'manager']);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.valid-lr.visits.store', $validLrSchool), ['team_leader_id' => $leader->id])
        ->assertRedirect();

    expect(ValidLrVisit::query()->count())->toBe(1);
    expect(ValidLrVisit::query()->sole()->team_leader_id)->toBe($leader->id);
});

test('team sees the school count beside the validated count and completing snapshots the personnel', function () {
    $validLrSchool = seedValidLrReferenceData();
    ['school' => $school, 'user' => $user] = validLrSchoolAccount();
    encodeValidLrEnrolment($school);
    $this->actingAs($user)->get(route('school.valid-lr.index'));
    $visit = ValidLrVisit::query()->sole();
    $this->actingAs($user)->put(route('school.valid-lr.update'), ['items' => countEveryValidLrItem($visit, 7)]);
    $this->actingAs($user)->post(route('school.valid-lr.submit'))->assertSessionHasNoErrors();

    $admin = User::factory()->admin()->create();
    $item = $visit->items()->where('section', 'lr')->first();

    $this->actingAs($admin)
        ->get(route('admin.valid-lr.visits.edit', $visit))
        ->assertInertia(fn (Assert $page) => $page
            ->where('visit.stage', 'ready')
            ->where('visit.personnel.lr_designate', 'LR Designate')
            ->where('checklist.ready', true)
            ->where("liveSchoolCounts.{$item->id}", 7));

    $this->actingAs($admin)->put(route('admin.valid-lr.visits.update', $visit), [
        'status' => 'completed',
        'visited_at' => '2026-11-10 09:00:00',
        'monitoring_team' => 'Team A',
        'school_representative' => 'Head Teacher',
        'prepared_by' => 'Monitor',
        'items' => [['id' => $item->id, 'actual_quantity' => 6, 'school_quantity' => 999]],
    ])->assertSessionHasNoErrors();

    $item->refresh();
    expect($item->actual_quantity)->toBe(6);
    expect($item->school_quantity)->toBe(7);

    $school->update(['lr_designate' => 'Someone Else']);
    $visit->refresh();
    expect($visit->personnel()['lr_designate'])->toBe('LR Designate');

    // A completed visit is closed to further school changes.
    $this->actingAs($user)
        ->put(route('school.valid-lr.update'), ['items' => [['id' => $item->id, 'school_quantity' => 1]]])
        ->assertStatus(422);

    $this->actingAs($admin)
        ->get(route('admin.valid-lr.visits.print', $visit))
        ->assertInertia(fn (Assert $page) => $page->where('visit.personnel.lr_designate', 'LR Designate'));

    $csv = $this->actingAs($admin)->get(route('admin.valid-lr.report.export'))->streamedContent();
    expect($csv)->toContain('"LR Designate","ICT Coordinator"')->and($csv)->toContain('Head Teacher');
});

test('live board shows preparation and validation progress to division monitors', function () {
    $validLrSchool = seedValidLrReferenceData();
    ValidLrSchool::factory()->create();
    ['user' => $user] = validLrSchoolAccount();
    $this->actingAs($user)->get(route('school.valid-lr.index'));
    $visit = ValidLrVisit::query()->sole();
    $item = $visit->items()->where('section', 'lr')->first();
    $this->actingAs($user)->put(route('school.valid-lr.update'), ['items' => [['id' => $item->id, 'school_quantity' => 3]]]);

    foreach (['cidchief', 'sds', 'asds', 'manager'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->get(route('admin.valid-lr.board'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('AdminValidLrBoard')
                ->where('summary.schools', 2)
                ->where('summary.not_started', 1)
                ->where('summary.stages.awaiting_school', 1)
                ->has('visits.data', 1)
                ->where('visits.data.0.beis_school_id', '304205')
                ->where('visits.data.0.stage', 'awaiting_school')
                ->where('visits.data.0.school_counted', 1)
                ->where('visits.data.0.enrolment_complete', false)
                ->where('visits.data.0.missing_profile', []));
    }

    $this->actingAs(User::factory()->create(['role' => 'cidchief']))
        ->get(route('admin.valid-lr.board', ['stage' => 'completed']))
        ->assertInertia(fn (Assert $page) => $page->has('visits.data', 0));

    $this->actingAs($user)->get(route('admin.valid-lr.board'))->assertForbidden();
});

test('school personnel can be recorded on the school profile', function () {
    $municipality = Municipality::factory()->create();
    $district = District::factory()->create(['municipality_id' => $municipality->id]);
    $school = School::factory()->create(['district_id' => $district->id, 'municipality_id' => $municipality->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.schools.update', $school), [
            'school_id' => $school->school_id,
            'school_name' => $school->school_name,
            'school_type' => $school->school_type,
            'district_id' => $district->id,
            'municipality_id' => $municipality->id,
            'school_head' => 'Head Teacher',
            'lr_designate' => 'Ana Cruz',
            'ict_coordinator' => 'Ben Reyes',
            'math_coordinator' => 'Carla Diaz',
            'science_coordinator' => 'Dan Lim',
            'property_custodian' => 'Eva Tan',
        ])
        ->assertSessionHasNoErrors();

    $school->refresh();
    expect($school->lr_designate)->toBe('Ana Cruz');
    expect($school->science_coordinator)->toBe('Dan Lim');
});

test('lr managers, ito, superadmin and system admin can correct lrmis counts on a visit', function () {
    $validLrSchool = seedValidLrReferenceData();
    $visit = app(ValidLrVisitService::class)->start($validLrSchool, null);
    $science = $visit->items()->where('section', 'lr')->where('group_label', 'Grade 7')->where('label', 'Science')->sole();
    $manager = User::factory()->create(['role' => 'manager', 'name' => 'LR Manager']);

    $this->actingAs($manager)
        ->put(route('admin.valid-lr.visits.lrmis', $visit), ['items' => [['id' => $science->id, 'lrmis_quantity' => 840]]])
        ->assertSessionHasNoErrors();

    $science->refresh();
    expect($science->lrmis_quantity)->toBe(840);
    expect($science->lrmis_original_quantity)->toBe(832);
    expect($science->lrmis_adjusted_by)->toBe($manager->id);
    expect($validLrSchool->lrmisCounts()->where('grade_level', 'Grade 7')->where('learning_area', 'Science')->value('total'))->toBe(840);

    $this->actingAs(User::factory()->create(['role' => 'ito']))
        ->put(route('admin.valid-lr.visits.lrmis', $visit), ['items' => [['id' => $science->id, 'lrmis_quantity' => 845]]]);
    expect($science->refresh()->lrmis_original_quantity)->toBe(832);

    $this->actingAs($manager)
        ->get(route('admin.valid-lr.visits.edit', $visit))
        ->assertInertia(fn (Assert $page) => $page
            ->where('canAdjustLrmis', true)
            ->where('canEdit', false));

    foreach (['librarian', 'cidchief', 'sds', 'admin'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->put(route('admin.valid-lr.visits.lrmis', $visit), ['items' => [['id' => $science->id, 'lrmis_quantity' => 1]]])
            ->assertForbidden();
    }

    expect($science->refresh()->lrmis_quantity)->toBe(845);
});
