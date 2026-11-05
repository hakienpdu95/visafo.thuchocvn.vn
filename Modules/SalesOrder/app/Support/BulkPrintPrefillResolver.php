<?php

namespace Modules\SalesOrder\Support;

use Illuminate\Support\Collection;
use Modules\GoodsReceipt\Models\ProductBatch;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Models\SalesOrderItem;

class BulkPrintPrefillResolver
{
    /** @return array<string, array{origin: 'history'|'customer'|null, batch_id: ?string, batch_text: ?string, vendor_id: ?string, vendor_name: ?string, partner_product_id: ?string, supplier_name: ?string, printed: int, has_batches: bool}> */
    public function forOrder(SalesOrder $order): array
    {
        $items = $order->items;
        $logs = PrintLog::query()
            ->whereIn('order_item_id', $items->pluck('id'))
            ->with(['productBatch.goodsReceipt.vendor', 'vendor:id,name', 'partnerProduct:id,vendor_id'])
            ->orderByRaw('status = ? desc', [PrintLogStatus::Active->value])
            ->latest('created_at')->latest('id')
            ->get()
            ->groupBy('order_item_id');

        $productIds = $items->pluck('product_id')->filter()->unique()->values();
        $productsWithBatches = ProductBatch::query()
            ->whereIn('product_id', $productIds)
            ->distinct()
            ->pluck('product_id')
            ->flip();

        $customerSources = $this->customerSources($order, $productIds);

        return $items->mapWithKeys(function (SalesOrderItem $item) use ($logs, $productsWithBatches, $customerSources) {
            $itemLogs = $logs->get($item->id, collect());
            $active = $itemLogs->where('status', PrintLogStatus::Active);
            $base = [
                'printed'     => $active->count(),
                'printed_groups' => collect(PrintLog::splitOf($active))
                    ->map(fn (int $qty, string $weight) => ['weight' => (float) $weight, 'qty' => $qty])->values()->all(),
                'has_batches' => $item->product_id !== null && $productsWithBatches->has($item->product_id),
            ];

            if ($last = $itemLogs->first()) {
                return [$item->id => $base + [
                    'origin'        => 'history',
                    'batch_id'      => $last->product_batch_id,
                    'batch_text'    => $last->productBatch ? self::batchText($last->productBatch) : null,
                    'vendor_id'     => $last->vendor_id,
                    'vendor_name'   => $last->vendor?->name,
                    // Chỉ điền sẵn khi hàng hóa NCC vẫn thuộc đúng NCC của tem (tem cũ: null).
                    'partner_product_id' => $last->partnerProduct?->vendor_id === $last->vendor_id ? $last->partner_product_id : null,
                    'supplier_name' => $last->vendor_id ? null : $last->supplier_name,
                ]];
            }

            // Chưa in lần nào: điền nguồn cung theo lần in gần nhất cùng khách hàng + cùng sản phẩm; lô không điền — nhân viên kho tự chọn đúng lô thực tế.
            if ($item->product_id !== null && ($prev = $customerSources->get($item->product_id))) {
                return [$item->id => $base + [
                    'origin'        => 'customer',
                    'batch_id'      => null,
                    'batch_text'    => null,
                    'vendor_id'     => $prev->vendor_id,
                    'vendor_name'   => $prev->vendor?->name,
                    'partner_product_id' => $prev->partnerProduct?->vendor_id === $prev->vendor_id ? $prev->partner_product_id : null,
                    'supplier_name' => $prev->vendor_id ? null : $prev->supplier_name,
                ]];
            }

            return [$item->id => $base + [
                'origin'        => null,
                'batch_id'      => null,
                'batch_text'    => null,
                'vendor_id'     => null,
                'vendor_name'   => null,
                'partner_product_id' => null,
                'supplier_name' => null,
            ]];
        })->all();
    }

    /** @return Collection<string, PrintLog> product_id => tem đang lưu hành mới nhất có nguồn cung, ở các đơn khác của cùng khách hàng (theo customer_id, đơn import chưa liên kết thì theo tên khách hàng) */
    private function customerSources(SalesOrder $order, Collection $productIds): Collection
    {
        $customerName = trim((string) $order->customer_name);
        if (($order->customer_id === null && $customerName === '') || $productIds->isEmpty()) {
            return new Collection();
        }

        $latest = PrintLog::query()
            ->join('sales_order_items as soi', 'soi.id', '=', 'print_logs.order_item_id')
            ->join('sales_orders as so', 'so.id', '=', 'soi.order_id')
            ->when(
                $order->customer_id !== null,
                fn ($q) => $q->where('so.customer_id', $order->customer_id),
                fn ($q) => $q->where('so.customer_name', $customerName),
            )
            ->where('so.id', '!=', $order->id)
            ->whereNull('so.deleted_at')
            ->whereNull('soi.deleted_at')
            ->whereIn('soi.product_id', $productIds)
            ->where('print_logs.status', PrintLogStatus::Active->value)
            ->where(fn ($q) => $q->whereNotNull('print_logs.vendor_id')->orWhereNotNull('print_logs.supplier_name'))
            ->groupBy('soi.product_id')
            ->selectRaw('soi.product_id, max(print_logs.id) as log_id')
            ->pluck('log_id', 'product_id');

        $logs = PrintLog::query()
            ->whereIn('id', $latest->values())
            ->with(['vendor:id,name', 'partnerProduct:id,vendor_id'])
            ->get()
            ->keyBy('id');

        return $latest->map(fn (string $logId) => $logs->get($logId))->filter();
    }

    public static function batchText(ProductBatch $batch): string
    {
        $receipt = $batch->goodsReceipt;

        return $batch->batch_code . ' · ' . ($receipt?->vendor?->name ?? $receipt?->supplier_name ?? 'Chưa rõ NCC')
            . ($receipt?->receipt_date ? ' · nhập ' . $receipt->receipt_date->format('d/m/Y') : '');
    }
}
