<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Total LRs a school encoded in the national LRMIS, per grade level and
 * learning area (from `LRMIS_ENCODED.xlsx`).
 */
class ValidLrLrmisCount extends Model
{
    protected $fillable = [
        'valid_lr_school_id',
        'grade_level',
        'learning_area',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
        ];
    }

    public function validLrSchool(): BelongsTo
    {
        return $this->belongsTo(ValidLrSchool::class);
    }
}
