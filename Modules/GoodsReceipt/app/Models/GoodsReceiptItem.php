<?php

namespace Modules\GoodsReceipt\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;

class GoodsReceiptItem extends TenantAwareModel
{
    protected $fillable = [
        'goods_receipt_id',
        'product_id',
        'line_no',
        'product_name_raw',
        'unit_raw',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
