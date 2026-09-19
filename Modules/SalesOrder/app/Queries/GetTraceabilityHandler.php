<?php

namespace Modules\SalesOrder\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Compliance\Models\InternalFacility;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Support\TraceabilityData;

class GetTraceabilityHandler implements QueryHandlerInterface
{
    /** @return TraceabilityData|null null khi mã truy xuất không tồn tại */
    public function handle(QueryInterface $query): ?TraceabilityData
    {
        /** @var GetTraceabilityQuery $query */
        $log = PrintLog::query()
            ->where('trace_code', $query->traceCode)
            ->with(['orderItem.product.category', 'orderItem.salesOrder', 'attributes'])
            ->first();

        if ($log === null || $log->orderItem === null) {
            return null;
        }

        $item = $log->orderItem;
        $order = $item->salesOrder;
        $product = $item->product;

        // Lô nhập gần nhất trước thời điểm in tem (lô hàng thực tế được đóng gói); không có thì lấy lô mới nhất.
        $batchQuery = ProductBatch::query()->where('product_id', $item->product_id)
            ->with('goodsReceipt.vendor');
        $batch = (clone $batchQuery)->where('created_at', '<=', $log->created_at)->latest('created_at')->first()
            ?? (clone $batchQuery)->latest('created_at')->first();

        $receipt = $batch?->goodsReceipt;
        $supplier = $receipt?->vendor?->name ?: ($receipt?->supplier_name ?: ($log->supplier_name ?: 'Đang cập nhật'));

        $weight = str_replace('.', ',', rtrim(rtrim(number_format((float) $log->weight_per_label, 3, '.', ''), '0'), '.')) . ' kg';

        $orderShipped = $order !== null && $order->status !== 'pending';

        $timeline = [
            [
                'title'       => 'Thu hoạch / Nhập kho',
                'description' => $batch
                    ? 'Lô ' . $batch->batch_code . ' nhập kho từ ' . $supplier . '.'
                    : 'Đang cập nhật thông tin lô hàng.',
                'at'          => $receipt?->receipt_date ?? $batch?->created_at,
                'done'        => $batch !== null,
            ],
            [
                'title'       => 'Sơ chế / Đóng gói / Kiểm định chất lượng',
                'description' => 'Đóng gói ' . $weight . ' và gắn mã truy xuất.',
                'at'          => $log->created_at,
                'done'        => true,
            ],
            [
                'title'       => 'Xuất kho / Giao hàng',
                'description' => $orderShipped ? 'Đã xuất kho giao đến khách hàng.' : 'Đã lập lệnh xuất kho, chờ giao hàng.',
                'at'          => $order?->created_at,
                'done'        => $orderShipped,
            ],
        ];

        return new TraceabilityData(
            traceCode: $log->trace_code,
            productName: $product?->name ?? $item->product_name_raw ?? '—',
            productImage: $product?->image_url ?: null,
            categoryName: $product?->category?->name,
            weight: $weight,
            mfgDate: $log->mfg_date,
            expDate: $log->exp_date,
            attributes: $log->attributes
                ->map(fn ($a) => ['key' => $a->attribute_key, 'value' => (string) $a->attribute_value])
                ->values()->all(),
            company: $this->company(),
            supplierName: $supplier,
            batchCode: $batch?->batch_code,
            timeline: $timeline,
        );
    }

    /** @return array{name: string, address: string, hotline: string} */
    private function company(): array
    {
        $hq = InternalFacility::query()->where('type', 'headquarter')->first();

        return [
            'name'    => $hq?->name ?: config('trace.company_name', 'VISAFO'),
            'address' => $hq?->address ?: config('trace.company_address', ''),
            'hotline' => (string) config('trace.company_hotline', ''),
        ];
    }
}
