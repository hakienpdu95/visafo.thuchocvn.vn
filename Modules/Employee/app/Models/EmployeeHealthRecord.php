<?php

namespace Modules\Employee\Models;

use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Employee\Enums\HealthRecordType;
use Spatie\MediaLibrary\HasMedia;

class EmployeeHealthRecord extends Model implements HasMedia
{
    use HasUlids;
    use HasTenantMedia;

    protected $fillable = [
        'employee_id',
        'record_type',
        'issue_date',
        'expiry_date',
        'result',
        'certificate_number',
        'issued_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'record_type' => HealthRecordType::class,
            'issue_date'  => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function isExpiringWithinDays(int $days): bool
    {
        return $this->expiry_date !== null
            && ! $this->isExpired()
            && $this->expiry_date->lte(now()->addDays($days));
    }
}
