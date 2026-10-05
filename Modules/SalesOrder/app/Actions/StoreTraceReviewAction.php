<?php

namespace Modules\SalesOrder\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesOrder\Enums\TraceReviewType;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\TraceReview;

/** Lưu đánh giá / báo sự cố từ trang truy xuất — nội suy đơn bán, sản phẩm, lô nhập từ mã TXNG (không tin dữ liệu khách gửi). */
class StoreTraceReviewAction
{
    use AsAction;

    /** @param array{type: string, comment?: ?string, issue_category?: ?string, is_public_requested?: bool, quality_score?: ?int, delivery_score?: ?int, packaging_score?: ?int, traceability_score?: ?int} $data */
    public function handle(PrintLog $log, array $data): TraceReview
    {
        $isRating = $data['type'] === TraceReviewType::Rating->value;
        $item = $log->orderItem;

        return TraceReview::create([
            'trace_code'          => $log->trace_code,
            'print_log_id'        => $log->id,
            'sales_order_id'      => $item?->order_id,
            'product_id'          => $item?->product_id,
            'product_batch_id'    => $log->product_batch_id,
            'type'                => $data['type'],
            'quality_score'       => $isRating ? $data['quality_score'] : null,
            'delivery_score'      => $isRating ? $data['delivery_score'] : null,
            'packaging_score'     => $isRating ? $data['packaging_score'] : null,
            'traceability_score'  => $isRating ? $data['traceability_score'] : null,
            'issue_category'      => $isRating ? null : $data['issue_category'],
            'comment'             => trim((string) ($data['comment'] ?? '')) ?: null,
            // Sự cố không bao giờ công khai
            'is_public_requested' => $isRating && ! empty($data['is_public_requested']),
        ]);
    }
}
