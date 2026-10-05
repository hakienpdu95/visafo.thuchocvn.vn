<?php

namespace Modules\GoodsReceipt\Models;

use App\Traits\HasCreator;
use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Product\Models\FarmingBatch;
use Modules\Product\Models\Product;

class ProductBatch extends TenantAwareModel
{
    use HasCreator;

    protected $fillable = [
        'batch_code',
        'product_id',
        'goods_receipt_id',
        'farming_batch_id',
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

    /** Thông tin bổ sung động (EAV): HDSD, Liều dùng, Bảo quản... */
    public function extraAttributes(): HasMany
    {
        return $this->hasMany(BatchAttribute::class, 'batch_id')->orderBy('created_at')->orderBy('id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    /** Lô canh tác nguồn — chọn ở phiếu nhập kho; trang truy xuất chỉ đi theo khóa này. */
    public function farmingBatch(): BelongsTo
    {
        return $this->belongsTo(FarmingBatch::class);
    }

    /**
     * Lô canh tác hợp lệ để gắn: cùng NCC của phiếu nhập, mặt hàng NCC trỏ đúng sản phẩm này, chưa hủy.
     *
     * @param  string[]  $productIds
     * @return Builder<FarmingBatch>
     */
    public static function farmingBatchCandidates(?string $vendorId, array $productIds): Builder
    {
        return FarmingBatch::query()
            ->where('vendor_id', $vendorId ?? '')
            ->where('status', '!=', 'cancelled')
            ->whereHas('partnerProduct', fn (Builder $q) => $q->whereIn('product_id', $productIds))
            ->with(['partnerProduct:id,product_id', 'farmingSource:id,source_code,name'])
            ->orderByDesc('actual_harvest_date')
            ->orderByDesc('created_at');
    }

    public function qualityChecks(): HasMany
    {
        return $this->hasMany(BatchQualityCheck::class)->orderBy('checked_at');
    }

    public function permissionModule(): string
    {
        return 'goods_receipt';
    }
}
