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
        'resource_title_id',
        'grade_level_id',
        'title',
        'author',
        'quantity_delivered',
        'quantity_with_issue_defect',
        'remarks',
        'source',
        'supplier',
        'date_delivered',
        'ier_no',
        'publisher',
        'language',
        'subject',
        'volume',
        'edition',
        'copyright_year',
        'pages',
        'isbn',
        'attachment_path',
        'cover_image_path',
    ];

    protected function casts(): array
    {
        return [
            'copyright_year' => 'integer',
            'pages' => 'integer',
            'date_delivered' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function learningResourceType(): BelongsTo
    {
        return $this->belongsTo(LearningResourceType::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function resourceTitle(): BelongsTo
    {
        return $this->belongsTo(ResourceTitle::class);
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
