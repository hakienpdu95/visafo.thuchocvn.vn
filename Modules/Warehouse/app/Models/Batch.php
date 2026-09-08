<?php

namespace Modules\Warehouse\Models;

use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Product\Models\Product;
use Modules\Recall\Models\AdverseEventReport;
use Modules\Vendor\Models\Vendor;
use Modules\Warehouse\Enums\BatchStatus;

class Batch extends Model
{
    use HasUlids;
    use BelongsToOrganization;

    protected $fillable = [
        'inbound_receipt_id',
        'product_id',
        'vendor_id',
        'mfg_batch_number',
        'internal_batch_code',
        'mfg_date',
        'exp_date',
        'initial_qty',
        'current_qty',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'mfg_date'    => 'date',
            'exp_date'    => 'date',
            'initial_qty' => 'integer',
            'current_qty' => 'integer',
            'status'      => BatchStatus::class,
        ];
    }

    public function inboundReceipt(): BelongsTo
    {
        return $this->belongsTo(InboundReceipt::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BatchDocument::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(RetailItemTag::class);
    }

    public function adverseEventReports(): HasMany
    {
        return $this->hasMany(AdverseEventReport::class);
    }

    public function isExpired(): bool
    {
        return $this->exp_date->isPast();
    }

    public function isExpiringWithinDays(int $days): bool
    {
        return ! $this->isExpired() && $this->exp_date->lte(now()->addDays($days));
    }
}
