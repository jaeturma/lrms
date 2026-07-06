<?php

namespace App\Models;

use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory, SoftDeletes;

    public const SCHOOL_TYPES = [
        'Elementary',
        'Junior High School',
        'JHS and SHS',
        'SHS Only',
        'Integrated School',
    ];

    protected $fillable = [
        'district_id',
        'municipality_id',
        'barangay_id',
        'school_id',
        'school_name',
        'school_type',
        'school_head',
        'librarian',
        'property_custodian',
        'primary_mobile_no',
        'secondary_mobile_no',
        'email',
        'activation_requested_at',
        'user_id',
        'is_activated',
    ];

    protected function casts(): array
    {
        return [
            'is_activated' => 'boolean',
            'activation_requested_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'school_id';
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function learningResources(): HasMany
    {
        return $this->hasMany(LearningResource::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }
}
