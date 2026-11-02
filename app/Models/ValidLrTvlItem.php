<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A TVL tool/equipment item in a specialization package, with its standard
 * quantity per specialization.
 */
class ValidLrTvlItem extends Model
{
    protected $fillable = [
        'specialization',
        'item_name',
        'procurement_source',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }
}
