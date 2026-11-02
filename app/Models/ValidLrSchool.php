<?php

namespace App\Models;

use Database\Factories\ValidLrSchoolFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A school row from the national VALID-LR reference data (`_reference_data.xlsx`
 * allocation sheet), keyed by BEIS school ID. It may or may not exist in the
 * LRMS `schools` table; `school()` links them when it does.
 */
class ValidLrSchool extends Model
{
    /** @use HasFactory<ValidLrSchoolFactory> */
    use HasFactory;

    protected $fillable = [
        'beis_school_id',
        'region',
        'division',
        'district',
        'school_name',
        'street_address',
        'mother_school_id',
        'province',
        'municipality',
        'legislative_district',
        'barangay',
        'sector',
        'school_type',
        'curricular_offering',
        'sme_deliveries',
    ];

    protected function casts(): array
    {
        return [
            'sme_deliveries' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'beis_school_id';
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'beis_school_id', 'school_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ValidLrAllocation::class);
    }

    public function lrmisCounts(): HasMany
    {
        return $this->hasMany(ValidLrLrmisCount::class);
    }

    public function tvlPrograms(): HasMany
    {
        return $this->hasMany(ValidLrTvlProgram::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(ValidLrVisit::class);
    }

    public function offersElementary(): bool
    {
        return str_contains((string) $this->curricular_offering, 'ES')
            || $this->curricular_offering === 'All Offering';
    }

    public function offersSecondary(): bool
    {
        return str_contains((string) $this->curricular_offering, 'JHS')
            || str_contains((string) $this->curricular_offering, 'SHS')
            || $this->curricular_offering === 'All Offering';
    }
}
