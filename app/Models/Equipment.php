<?php

namespace App\Models;

use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    /** @use HasFactory<EquipmentFactory> */
    use HasFactory, SoftDeletes;

    public const CATEGORIES = [
        'ICT',
        'Science',
        'Mathematics',
        'TVL',
        'ALS',
        'Library',
        'SPED',
        'Other Learning Equipment',
    ];

    public const CONDITIONS = [
        'Excellent',
        'Good',
        'Fair',
        'Needs Repair',
        'Beyond Repair',
    ];

    public const STATUSES = [
        'Available',
        'In Use',
        'Borrowed',
        'Missing',
        'Lost',
        'Condemned',
        'Disposed',
    ];

    protected $table = 'equipment';

    protected $fillable = [
        'school_id',
        'item_code',
        'item_name',
        'category',
        'brand',
        'model',
        'specifications',
        'manufacturer',
        'serial_number',
        'property_number',
        'barcode',
        'qr_code',
        'acquisition_date',
        'acquisition_cost',
        'funding_source',
        'supplier',
        'warranty_expires_on',
        'useful_life_years',
        'current_location',
        'assigned_personnel',
        'condition',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'acquisition_cost' => 'decimal:2',
            'warranty_expires_on' => 'date',
            'useful_life_years' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(EquipmentMovement::class);
    }
}
