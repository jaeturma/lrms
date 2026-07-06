<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppContent extends Model
{
    protected $fillable = [
        'key',
        'title',
        'body',
    ];
}
