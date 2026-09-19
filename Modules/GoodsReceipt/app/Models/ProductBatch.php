<?php

namespace Modules\GoodsReceipt\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
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

    /**
     * HSD tự tính = NSX + Product::shelf_life_days. Trả null nếu thiếu NSX
     * hoặc sản phẩm chưa khai báo số ngày bảo quản.
     */
    public function calculateExpDate(Carbon|string|null $mfgDate): ?Carbon
    {
        return $this->product?->calculateExpDate($mfgDate);
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
