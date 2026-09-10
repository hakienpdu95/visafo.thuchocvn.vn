<?php

namespace Modules\Contract\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Traits\HasAutoCode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Contract\Enums\ContractStatus;
use Modules\Vendor\Models\Vendor;

class Contract extends TenantAwareModel
{
    use HasAutoCode;

    public function autoCodeColumn(): string
    {
        return 'contract_number';
    }

    public function autoCodeSequenceType(): string
    {
        return 'contract';
    }

    protected $fillable = [
        'vendor_id',
        'contract_type_id',
        'contract_number',
        'name',
        'total_value',
        'start_date',
        'end_date',
        'is_auto_renew',
        'renewal_period_months',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_value'            => 'decimal:2',
            'start_date'             => 'date',
            'end_date'               => 'date',
            'is_auto_renew'          => 'boolean',
            'renewal_period_months'  => 'integer',
            'status'                 => ContractStatus::class,
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function isDueForRenewal(): bool
    {
        return $this->is_auto_renew
            && $this->status === ContractStatus::Active
            && $this->end_date !== null
            && $this->end_date->lte(today());
    }
}
