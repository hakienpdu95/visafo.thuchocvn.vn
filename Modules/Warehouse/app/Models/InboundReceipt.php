<?php

namespace Modules\Warehouse\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Vendor\Models\Vendor;
use Modules\Warehouse\Enums\InboundReceiptStatus;

class InboundReceipt extends TenantAwareModel
{
    protected $fillable = [
        'vendor_id',
        'receipt_number',
        'received_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'received_date' => 'date',
            'status'        => InboundReceiptStatus::class,
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(InboundReceiptDocument::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }
}
