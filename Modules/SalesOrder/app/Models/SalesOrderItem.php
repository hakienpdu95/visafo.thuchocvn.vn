<?php

namespace Modules\SalesOrder\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Product\Models\Product;

class SalesOrderItem extends TenantAwareModel
{
    protected $fillable = [
        'order_id',
        'product_id',
        'line_no',
        'product_name_raw',
        'unit_raw',
        'requested_qty',
        'actual_qty',
        'printed_qty',
    ];

    protected function casts(): array
    {
        return [
            'requested_qty' => 'decimal:3',
            'actual_qty'    => 'decimal:3',
            'printed_qty'   => 'decimal:3',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'order_id');
    }

    public function printLogs(): HasMany
    {
        return $this->hasMany(PrintLog::class, 'order_item_id');
    }

    /** Alias của order() — template tem dùng $item->salesOrder. */
    public function salesOrder(): BelongsTo
    {
        return $this->order();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
