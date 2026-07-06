<?php

namespace App\Models;

use Database\Factories\DistrictFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    /** @use HasFactory<DistrictFactory> */
    use HasFactory;

    protected $fillable = ['municipality_id', 'name'];

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }
}
