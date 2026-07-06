<?php

namespace App\Models;

use Database\Factories\LearningResourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LearningResource extends Model
{
    /** @use HasFactory<LearningResourceFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id',
        'learning_resource_type_id',
        'title',
        'quantity_delivered',
        'quantity_with_issue_defect',
        'remarks',
        'publisher',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function learningResourceType(): BelongsTo
    {
        return $this->belongsTo(LearningResourceType::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(LearningResourceInventory::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
