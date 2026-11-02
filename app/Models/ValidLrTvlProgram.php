<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A TVL specialization offered by a school, with the years its package was
 * delivered and its procurement status.
 */
class ValidLrTvlProgram extends Model
{
    protected $fillable = [
        'valid_lr_school_id',
        'specialization',
        'delivery_years',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'delivery_years' => 'array',
        ];
    }

    public function validLrSchool(): BelongsTo
    {
        return $this->belongsTo(ValidLrSchool::class);
    }
}
