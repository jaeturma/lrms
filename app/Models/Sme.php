<?php

namespace App\Models;

use Database\Factories\SmeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sme extends Model
{
    /** @use HasFactory<SmeFactory> */
    use HasFactory, SoftDeletes;

    public const CATEGORIES = [
        'Science',
        'Mathematics',
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

    protected $table = 'sme';

    protected $fillable = [
        'school_id',
        'sme_catalog_item_id',
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
        'date_delivered',
        'ier_no',
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
            'date_delivered' => 'date',
            'warranty_expires_on' => 'date',
            'useful_life_years' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(SmeCatalogItem::class, 'sme_catalog_item_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(SmeMovement::class);
    }
}
