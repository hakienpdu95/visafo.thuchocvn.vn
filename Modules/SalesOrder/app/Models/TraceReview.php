<?php

namespace Modules\SalesOrder\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\Product\Models\Product;
use Modules\SalesOrder\Enums\TraceReviewStatus;
use Modules\SalesOrder\Enums\TraceReviewType;

/**
 * Đánh giá lô hàng / báo sự cố gửi từ trang truy xuất công khai — luôn gắn với mã TXNG khách đã quét.
 * Chỉ bản ghi rating đã duyệt mới được tính điểm & hiện công khai (nhận xét chỉ hiện khi khách đồng ý công khai).
 */
class TraceReview extends TenantAwareModel
{
    /** Phân loại sự cố (form "Phản hồi & báo sự cố"). */
    public const ISSUE_CATEGORIES = [
        'product_quality' => 'Chất lượng sản phẩm',
        'packaging'       => 'Bao bì / tem',
        'delivery'        => 'Giao hàng',
        'traceability'    => 'Thông tin truy xuất',
        'other'           => 'Khác',
    ];

    /** Tiêu chí chấm điểm (cột => nhãn). */
    public const SCORES = [
        'quality_score'      => 'Chất lượng sản phẩm',
        'delivery_score'     => 'Giao hàng',
        'packaging_score'    => 'Đóng gói / bao bì',
        'traceability_score' => 'Thông tin truy xuất',
    ];

    protected $fillable = [
        'trace_code', 'print_log_id', 'sales_order_id', 'product_id', 'product_batch_id', 'type',
        'quality_score', 'delivery_score', 'packaging_score', 'traceability_score',
        'issue_category', 'comment', 'is_public_requested', 'status', 'reviewed_by', 'reviewed_at',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'type'                => TraceReviewType::class,
            'status'              => TraceReviewStatus::class,
            'is_public_requested' => 'boolean',
            'reviewed_at'         => 'datetime',
        ];
    }

    public function printLog(): BelongsTo
    {
        return $this->belongsTo(PrintLog::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productBatch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Đánh giá đã duyệt — nguồn duy nhất cho điểm & danh sách công khai. */
    public function scopeApprovedRatings(Builder $query): Builder
    {
        return $query->where('type', TraceReviewType::Rating->value)->where('status', TraceReviewStatus::Approved->value);
    }

    /** Điểm trung bình 4 tiêu chí của một đánh giá. */
    public function averageScore(): ?float
    {
        $scores = array_filter(array_map(fn ($col) => $this->{$col}, array_keys(self::SCORES)), fn ($v) => $v !== null);

        return $scores ? round(array_sum($scores) / count($scores), 1) : null;
    }

    public function issueCategoryLabel(): ?string
    {
        return self::ISSUE_CATEGORIES[$this->issue_category] ?? $this->issue_category;
    }

    public function permissionModule(): string
    {
        return 'trace_log';
    }
}
