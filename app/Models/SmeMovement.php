<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmeMovement extends Model
{
    public const TYPES = [
        'created',
        'status_change',
        'condition_change',
        'reassigned',
        'relocated',
        'updated',
        'deleted',
    ];

    protected $fillable = [
        'sme_id',
        'school_id',
        'user_id',
        'type',
        'from_value',
        'to_value',
        'notes',
    ];

    public function sme(): BelongsTo
    {
        return $this->belongsTo(Sme::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
