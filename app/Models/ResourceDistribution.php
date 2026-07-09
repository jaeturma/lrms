<?php

namespace App\Models;

use Database\Factories\ResourceDistributionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourceDistribution extends Model
{
    /** @use HasFactory<ResourceDistributionFactory> */
    use HasFactory;

    public const STATUSES = [
        'pending',
        'received',
        'cancelled',
    ];

    protected $fillable = [
        'reference_code',
        'school_id',
        'learning_resource_type_id',
        'resource_title_id',
        'title',
        'publisher',
        'quantity',
        'quantity_damaged',
        'status',
        'notes',
        'created_by',
        'received_by',
        'learning_resource_id',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'quantity_damaged' => 'integer',
            'received_at' => 'datetime',
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

    public function resourceTitle(): BelongsTo
    {
        return $this->belongsTo(ResourceTitle::class);
    }

    public function learningResource(): BelongsTo
    {
        return $this->belongsTo(LearningResource::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
