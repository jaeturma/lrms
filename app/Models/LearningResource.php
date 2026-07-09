<?php

namespace App\Models;

use Database\Factories\LearningResourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class LearningResource extends Model
{
    /** @use HasFactory<LearningResourceFactory> */
    use HasFactory;

    use SoftDeletes;

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

    /**
     * Group school + catalog-title combinations that currently have more
     * than one (non-deleted) LearningResource row, oldest-first within each
     * group. Used to detect and merge duplicates produced before
     * ResourceDistributionService::receive() reused existing rows.
     *
     * @return Collection<int, array{school_id: int, resource_title_id: int, total: int, resource_ids: array<int, int>}>
     */
    public static function duplicateGroups(): Collection
    {
        return static::query()
            ->select('school_id', 'resource_title_id')
            ->selectRaw('count(*) as total')
            ->whereNotNull('resource_title_id')
            ->groupBy('school_id', 'resource_title_id')
            ->havingRaw('count(*) > 1')
            ->get()
            ->map(function (self $row): array {
                $ids = static::query()
                    ->where('school_id', $row->school_id)
                    ->where('resource_title_id', $row->resource_title_id)
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->pluck('id')
                    ->all();

                return [
                    'school_id' => (int) $row->school_id,
                    'resource_title_id' => (int) $row->resource_title_id,
                    'total' => count($ids),
                    'resource_ids' => $ids,
                ];
            });
    }
}
