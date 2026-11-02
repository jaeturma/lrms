<?php

namespace App\Models;

use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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
        'lr_designate',
        'ict_coordinator',
        'math_coordinator',
        'science_coordinator',
        'primary_mobile_no',
        'secondary_mobile_no',
        'email',
        'logo_path',
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

    /**
     * Public URL of the school's uploaded logo, if any.
     */
    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
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

    public function learningResourceInventories(): HasManyThrough
    {
        return $this->hasManyThrough(LearningResourceInventory::class, LearningResource::class);
    }

    public function ictEquipment(): HasMany
    {
        return $this->hasMany(IctEquipment::class);
    }

    public function otherEquipment(): HasMany
    {
        return $this->hasMany(OtherEquipment::class);
    }

    public function sme(): HasMany
    {
        return $this->hasMany(Sme::class);
    }
}
