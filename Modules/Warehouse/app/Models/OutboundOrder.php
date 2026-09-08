<?php

namespace Modules\Warehouse\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Warehouse\Enums\OutboundOrderStatus;

class OutboundOrder extends TenantAwareModel
{
    protected $fillable = [
        'order_number',
        'order_type',
        'dealer_name',
        'dealer_phone',
        'dealer_address',
        'status',
        'ordered_at',
        'completed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status'       => OutboundOrderStatus::class,
            'ordered_at'   => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function pickedBatches(): HasMany
    {
        return $this->hasMany(OutboundPickedBatch::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(RetailItemTag::class);
    }

    public function totalQuantity(): int
    {
        return (int) $this->pickedBatches->sum('quantity');
    }
}
