<?php

namespace Modules\GoodsReceipt\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Product\Models\Product;

class ProductBatch extends TenantAwareModel
{
    protected $fillable = [
        'batch_code',
        'product_id',
        'goods_receipt_id',
        'initial_qty',
        'current_qty',
        'mfg_date',
        'exp_date',
    ];

    protected function casts(): array
    {
        return [
            'initial_qty' => 'decimal:3',
            'current_qty' => 'decimal:3',
            'mfg_date'    => 'date',
            'exp_date'    => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }
}
