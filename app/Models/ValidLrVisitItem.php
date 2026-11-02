<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidLrVisitItem extends Model
{
    public const SECTION_LEARNING_RESOURCES = 'lr';

    public const SECTION_SME_CENTRAL_OFFICE = 'sme_co';

    public const SECTION_SME_DOWNLOADED = 'sme_dl';

    public const SECTION_TVL = 'tvl';

    public const SECTION_LEARNERS_WITH_DISABILITIES = 'im_lwd';

    public const SECTION_ARAL = 'aral';

    public const MOV_OPTIONS = [
        'Delivery Receipts',
        'Distribution List (for Elementary)',
        'Inventory Records (by School)',
        'LRMIS Printout/Screenshot',
        'Photo Documentation',
        'None',
    ];

    public const ARAL_PROGRAMS = [
        'aral_reading_2025' => 'ARAL Reading (SY 2025–2026)',
        'summer_aral_2026' => 'Summer ARAL (May 6–June 2, 2026)',
        'aral_reading_2026' => 'ARAL Reading (SY 2026–2027)',
        'aral_math_2026' => 'ARAL Math (SY 2026–2027)',
    ];

    protected $fillable = [
        'valid_lr_visit_id',
        'section',
        'sort_order',
        'group_label',
        'label',
        'title',
        'publisher',
        'unit',
        'procurement_source',
        'reference_quantity',
        'lrmis_quantity',
        'lrmis_original_quantity',
        'lrmis_adjusted_by',
        'lrmis_adjusted_at',
        'lrms_quantity',
        'distributed_quantity',
        'school_quantity',
        'actual_quantity',
        'enrolment',
        'year_received',
        'mov',
        'extra',
        'remarks_repairs',
        'remarks_missing',
        'remarks_others',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'reference_quantity' => 'integer',
            'lrmis_quantity' => 'integer',
            'lrmis_original_quantity' => 'integer',
            'lrmis_adjusted_at' => 'datetime',
            'lrms_quantity' => 'integer',
            'distributed_quantity' => 'integer',
            'school_quantity' => 'integer',
            'actual_quantity' => 'integer',
            'enrolment' => 'integer',
            'mov' => 'array',
            'extra' => 'array',
        ];
    }

    public function lrmisAdjuster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lrmis_adjusted_by');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(ValidLrVisit::class, 'valid_lr_visit_id');
    }
}
