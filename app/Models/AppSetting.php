<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * @param  array<int, string>  $keys
     * @return Collection<string, string|null>
     */
    public static function valuesFor(array $keys): Collection
    {
        return static::query()
            ->whereIn('key', $keys)
            ->pluck('value', 'key');
    }
}
