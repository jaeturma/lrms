<?php

namespace App\Models;

use Database\Factories\IctEquipmentCatalogItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IctEquipmentCatalogItem extends Model
{
    /** @use HasFactory<IctEquipmentCatalogItemFactory> */
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

    public function ictEquipment(): HasMany
    {
        return $this->hasMany(IctEquipment::class);
    }
}
