<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Standard SME package item per key stage, either procured by the Central
 * Office or through downloaded (RO/SDO) funds.
 */
class ValidLrSmeItem extends Model
{
    public const SOURCE_CENTRAL_OFFICE = 'central_office';

    public const SOURCE_DOWNLOADED = 'downloaded';

    public const KEY_STAGES = [
        'Grade 1 to Grade 3',
        'Grade 4 to Grade 6',
        'Junior High School',
        'Senior High School',
    ];

    protected $fillable = [
        'source',
        'group_label',
        'item_name',
        'unit',
        'key_stage',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }
}
