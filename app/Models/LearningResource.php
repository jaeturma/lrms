<?php

namespace App\Models;

use Database\Factories\LearningResourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningResource extends Model
{
    /** @use HasFactory<LearningResourceFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id',
        'resource_type',
        'issue_defect',
        'quantity',
        'publisher',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
