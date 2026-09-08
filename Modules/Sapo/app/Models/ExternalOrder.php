<?php

namespace Modules\Sapo\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalOrder extends TenantAwareModel
{
    protected $fillable = [
        'customer_id',
        'external_system',
        'external_order_code',
        'ordered_at',
        'total_amount',
        'status',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'ordered_at'   => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
