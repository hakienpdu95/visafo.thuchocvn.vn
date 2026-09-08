<?php

namespace Modules\Warehouse\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Modules\Warehouse\Enums\BatchStatus;
use Modules\Warehouse\Models\Batch;

class GetFefoSuggestionHandler implements QueryHandlerInterface
{
    /**
     * Trả về danh sách lô còn hàng của 1 sản phẩm, sắp theo exp_date tăng dần (FEFO),
     * kèm số lượng đề xuất lấy từ mỗi lô để đủ requestedQty.
     */
    public function handle(QueryInterface $query): Collection
    {
        /** @var GetFefoSuggestionQuery $query */
        $batches = Batch::where('product_id', $query->productId)
            ->where('status', BatchStatus::Available->value)
            ->where('current_qty', '>', 0)
            ->orderBy('exp_date')
            ->get();

        $remaining = $query->requestedQty;

        return $batches->map(function (Batch $batch) use (&$remaining) {
            $suggested = $remaining > 0 ? min($batch->current_qty, $remaining) : 0;
            $remaining = max(0, $remaining - $suggested);

            return [
                'batch'     => $batch,
                'suggested' => $suggested,
            ];
        });
    }
}
