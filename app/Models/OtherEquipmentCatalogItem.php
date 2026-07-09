<?php

namespace App\Models;

use Database\Factories\OtherEquipmentCatalogItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OtherEquipmentCatalogItem extends Model
{
    /** @use HasFactory<OtherEquipmentCatalogItemFactory> */
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

    public function otherEquipment(): HasMany
    {
        return $this->hasMany(OtherEquipment::class);
    }
}
