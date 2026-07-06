<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningResourceInventory extends Model
{
    public const STATUSES = [
        'available',
        'issued',
        'borrowed',
        'damaged',
        'lost',
        'condemned',
    ];

    protected $fillable = [
        'learning_resource_id',
        'available',
        'issued',
        'borrowed',
        'damaged',
        'lost',
        'condemned',
    ];

    protected function casts(): array
    {
        return [
            'available' => 'integer',
            'issued' => 'integer',
            'borrowed' => 'integer',
            'damaged' => 'integer',
            'lost' => 'integer',
            'condemned' => 'integer',
        ];
    }

    public function learningResource(): BelongsTo
    {
        return $this->belongsTo(LearningResource::class);
    }

    public function totalOnRecord(): int
    {
        return $this->available + $this->issued + $this->borrowed
            + $this->damaged + $this->lost + $this->condemned;
    }
}
