<?php

namespace App\Models;

use Database\Factories\ValidLrVisitFactory;
use Illuminate\Database\Eloquent\Builder;
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

    public const STAGE_AWAITING_SCHOOL = 'awaiting_school';

    public const STAGE_READY = 'ready';

    public const STAGE_VALIDATING = 'validating';

    public const STAGE_COMPLETED = 'completed';

    public const STAGES = [
        self::STAGE_AWAITING_SCHOOL => 'Waiting for school',
        self::STAGE_READY => 'Ready for validation',
        self::STAGE_VALIDATING => 'Validation ongoing',
        self::STAGE_COMPLETED => 'Completed',
    ];

    /**
     * Roles that can schedule, override, delete and edit any visit. A visit's
     * assigned team leader can also edit it; every other VALID-LR role only
     * watches.
     */
    public const ADMIN_ROLES = ['admin', 'superadmin', 'sysadmin', 'ito'];

    /**
     * Roles that can correct a visit's LRMIS-encoded counts to match the
     * school's actual LRMIS records.
     */
    public const LRMIS_EDITOR_ROLES = ['superadmin', 'sysadmin', 'manager', 'ito'];

    /**
     * Roles that can schedule monitoring and assign team leaders.
     */
    public const SCHEDULER_ROLES = ['admin', 'superadmin', 'sysadmin', 'ito', 'manager'];

    /**
     * School personnel shown on the profile, visit and printouts; all are
     * required before a school can submit for validation.
     *
     * @var array<string, string>
     */
    public const PERSONNEL = [
        'school_head' => 'School Head',
        'lr_designate' => 'LR Designate',
        'ict_coordinator' => 'ICT Coordinator',
        'math_coordinator' => 'Math Coordinator',
        'science_coordinator' => 'Science Coordinator',
        'property_custodian' => 'Property Custodian',
    ];

    /**
     * Roles that may be assigned as a monitoring team leader.
     */
    public const TEAM_LEADER_ROLES = ['admin', 'superadmin', 'sysadmin', 'ito', 'manager', 'librarian', 'supply', 'cidchief', 'asds', 'sds'];

    /**
     * Sections the school must count before it can submit.
     */
    public const COUNTED_SECTIONS = [
        ValidLrVisitItem::SECTION_LEARNING_RESOURCES,
        ValidLrVisitItem::SECTION_SME_CENTRAL_OFFICE,
        ValidLrVisitItem::SECTION_SME_DOWNLOADED,
        ValidLrVisitItem::SECTION_TVL,
    ];

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
        'team_leader_id',
        'scheduled_at',
        'school_submitted_at',
        'school_submitted_by',
        'override_reason',
        'overridden_by',
        'overridden_at',
        'validation_started_at',
        'status',
        'visited_at',
        'monitoring_team',
        'school_representative',
        'school_personnel',
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
            'scheduled_at' => 'datetime',
            'school_submitted_at' => 'datetime',
            'overridden_at' => 'datetime',
            'validation_started_at' => 'datetime',
            'indicators' => 'array',
            'school_personnel' => 'array',
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

    public function teamLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }

    public function schoolSubmitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'school_submitted_by');
    }

    public function overrider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }

    public function stage(): string
    {
        return match (true) {
            $this->status === 'completed' => self::STAGE_COMPLETED,
            $this->validation_started_at !== null => self::STAGE_VALIDATING,
            $this->school_submitted_at !== null || $this->overridden_at !== null => self::STAGE_READY,
            default => self::STAGE_AWAITING_SCHOOL,
        };
    }

    /**
     * Personnel names as they stood when the visit was completed, or the
     * school's current profile while the visit is still open.
     *
     * @return array<string, ?string>
     */
    public function personnel(): array
    {
        if (! $this->isOpen() && is_array($this->school_personnel)) {
            return array_merge(array_fill_keys(array_keys(self::PERSONNEL), null), $this->school_personnel);
        }

        $school = $this->validLrSchool?->school;

        return collect(self::PERSONNEL)
            ->map(fn (string $label, string $field): ?string => $school?->{$field} ?: null)
            ->all();
    }

    /**
     * @param  Builder<ValidLrVisit>  $query
     */
    public function scopeInStage(Builder $query, string $stage): void
    {
        match ($stage) {
            self::STAGE_COMPLETED => $query->where('status', 'completed'),
            self::STAGE_VALIDATING => $query->where('status', '!=', 'completed')->whereNotNull('validation_started_at'),
            self::STAGE_READY => $query->where('status', '!=', 'completed')->whereNull('validation_started_at')
                ->where(fn (Builder $nested) => $nested->whereNotNull('school_submitted_at')->orWhereNotNull('overridden_at')),
            self::STAGE_AWAITING_SCHOOL => $query->where('status', '!=', 'completed')->whereNull('validation_started_at')
                ->whereNull('school_submitted_at')->whereNull('overridden_at'),
            default => null,
        };
    }

    public function isOpen(): bool
    {
        return $this->status !== 'completed';
    }

    public function canBeEditedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return self::isAdmin($user) || ($this->team_leader_id !== null && $this->team_leader_id === $user->id);
    }

    public static function isAdmin(?User $user): bool
    {
        return in_array($user?->role, self::ADMIN_ROLES, true);
    }

    /**
     * @return array<int, array{id: int, name: string, role: string}>
     */
    public static function teamLeaderOptions(): array
    {
        return User::query()
            ->whereIn('role', self::TEAM_LEADER_ROLES)
            ->orderBy('name')
            ->get(['id', 'name', 'role'])
            ->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name, 'role' => $user->role])
            ->all();
    }

    public static function canAdjustLrmis(?User $user): bool
    {
        return in_array($user?->role, self::LRMIS_EDITOR_ROLES, true);
    }

    public static function canSchedule(?User $user): bool
    {
        return in_array($user?->role, self::SCHEDULER_ROLES, true);
    }
}
