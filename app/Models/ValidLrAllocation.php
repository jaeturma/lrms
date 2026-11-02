<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidLrAllocation extends Model
{
    protected $fillable = [
        'valid_lr_school_id',
        'grade_level',
        'learning_area',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function validLrSchool(): BelongsTo
    {
        return $this->belongsTo(ValidLrSchool::class);
    }
}
