<?php

namespace Modules\Compliance\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Compliance\Enums\WarningCategory;
use Modules\Compliance\Enums\WarningSeverity;
use Modules\Compliance\Enums\WarningStatus;

class ComplianceWarning extends TenantAwareModel
{
    protected $fillable = [
        'warnable_type',
        'warnable_id',
        'category',
        'title',
        'message',
        'due_date',
        'severity',
        'status',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'category'        => WarningCategory::class,
            'due_date'        => 'date',
            'severity'        => WarningSeverity::class,
            'status'          => WarningStatus::class,
            'acknowledged_at' => 'datetime',
            'resolved_at'     => 'datetime',
        ];
    }

    public function warnable(): MorphTo
    {
        return $this->morphTo();
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function daysRemaining(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->due_date, false);
    }
}
