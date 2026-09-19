<?php

namespace Modules\SalesOrder\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Models\SalesOrderItem;

class BulkPrintSalesOrderLabelsAction
{
    use AsAction;

    /**
     * In tem cho mọi dòng còn thiếu (yêu cầu > đã in) mà sản phẩm có shelf_life_days.
     * Dòng chưa cấu hình HSD bị bỏ qua — nhân viên kho phải in tay để khai báo HSD.
     *
     * @return array{logs: PrintLog[], total: int, printed: int, manual: int, items: array<int, array{id: string, printed_qty: string, printed_qty_raw: float}>}
     */
    public function handle(SalesOrder $order, ?string $printedBy): array
    {
        return DB::transaction(function () use ($order, $printedBy) {
            // Khóa dòng hàng để hai lần bấm đồng thời không in trùng phần còn lại.
            $items = SalesOrderItem::query()
                ->where('order_id', $order->id)
                ->with('product')
                ->orderBy('line_no')
                ->lockForUpdate()
                ->get();

            $today = now()->startOfDay();
            $logs = [];
            $updated = [];
            $total = 0;
            $manual = 0;

            foreach ($items as $item) {
                $remaining = round((float) $item->requested_qty - (float) $item->printed_qty, 3);
                if ($remaining <= 0) {
                    continue;
                }

                $total++;

                $exp = $item->product?->calculateExpDate($today);
                if ($exp === null) {
                    $manual++;

                    continue;
                }

                $logs[] = PrintLog::create([
                    'order_item_id'    => $item->id,
                    'weight_per_label' => $remaining,
                    'label_count'      => 1,
                    'mfg_date'         => $today->toDateString(),
                    'exp_date'         => $exp->toDateString(),
                    'supplier_name'    => null,
                    'printed_by'       => $printedBy,
                ]);

                $item->increment('printed_qty', $remaining);
                $printedQty = (float) $item->fresh()->printed_qty;

                $updated[] = [
                    'id'              => $item->id,
                    'printed_qty'     => number_format($printedQty, 3),
                    'printed_qty_raw' => $printedQty,
                ];
            }

            return [
                'logs'    => $logs,
                'total'   => $total,
                'printed' => count($logs),
                'manual'  => $manual,
                'items'   => $updated,
            ];
        });
    }
}
