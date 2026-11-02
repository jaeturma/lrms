<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Title and publisher delivered to a region for a grade level and learning
 * area (from the `LRD_Elem` / `LRD_HS` sheets of `_reference_data.xlsx`).
 */
class ValidLrTitle extends Model
{
    protected $fillable = [
        'region',
        'grade_level',
        'learning_area',
        'title',
        'publisher',
    ];
}
