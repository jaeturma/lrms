<?php

namespace App\Models;

use Database\Factories\ValidLrVisitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One VALID-LR monitoring visit to a school. Its items are a snapshot of the
 * reference data taken when the visit was started, plus what the monitors
 * actually counted.
 */
class ValidLrVisit extends Model
{
    /** @use HasFactory<ValidLrVisitFactory> */
    use HasFactory;

    public const STATUSES = ['draft', 'completed'];

    public const DELIVERY_STATUSES = ['Complete', 'Incomplete'];

    /**
     * @var array<string, string>
     */
    public const INDICATORS = [
        'lrmis_account' => 'Does the school have an active LRMIS account and access to its school records?',
        'deliveries_recorded' => 'Have the Learning Resource (LR) deliveries received by the school from 2024 to 2026, as applicable, been recorded in LRMIS?',
        'inventory_matches' => "Does the school's actual physical inventory match the LR records in LRMIS?",
        'documents_available' => 'Are supporting documents, such as Inventory Custodian Slips (ICS) and Inspection and Acceptance Reports (IAR), available for verification, when applicable?',
    ];

    protected $fillable = [
        'valid_lr_school_id',
        'user_id',
        'status',
        'visited_at',
        'monitoring_team',
        'school_representative',
        'indicators',
        'lr_delivery_status',
        'sme_delivery_status',
        'tvl_delivery_status',
        'key_findings',
        'recommended_actions',
        'issues_concerns',
        'actions_taken',
        'prepared_by',
        'acknowledged_by',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'indicators' => 'array',
        ];
    }

    public function validLrSchool(): BelongsTo
    {
        return $this->belongsTo(ValidLrSchool::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ValidLrVisitItem::class)->orderBy('sort_order');
    }
}
