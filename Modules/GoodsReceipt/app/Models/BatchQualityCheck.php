<?php

namespace Modules\GoodsReceipt\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use App\Traits\HasCreator;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GoodsReceipt\Enums\QualityCheckResult;
use Modules\GoodsReceipt\Enums\QualityCheckStage;
use Modules\SalesOrder\Models\SalesOrderItem;

/**
 * Phiếu QC nội bộ của VISAFO: tiếp nhận / cảm quan (gắn lô nhập kho) và trước xuất (gắn dòng đơn bán).
 * Trang truy xuất công khai chỉ hiện khâu, kết quả và thời điểm — không hiện ghi chú, người kiểm.
 */
class BatchQualityCheck extends TenantAwareModel
{
    use HasCreator;

    protected $fillable = [
        'product_batch_id',
        'sales_order_item_id',
        'stage',
        'result',
        'checked_at',
        'checked_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'stage'      => QualityCheckStage::class,
            'result'     => QualityCheckResult::class,
            'checked_at' => 'datetime',
        ];
    }

    public function productBatch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class);
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function permissionModule(): string
    {
        return 'goods_receipt';
    }
}
