<?php

namespace App\Models;

use Database\Factories\SmeCatalogItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmeCatalogItem extends Model
{
    /** @use HasFactory<SmeCatalogItemFactory> */
    use HasFactory;

    protected $fillable = [
        'item_name',
        'category',
        'brand',
        'model',
        'specifications',
        'manufacturer',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function sme(): HasMany
    {
        return $this->hasMany(Sme::class);
    }
}
