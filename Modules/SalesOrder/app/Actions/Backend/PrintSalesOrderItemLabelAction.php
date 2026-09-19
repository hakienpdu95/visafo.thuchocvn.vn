<?php

namespace Modules\SalesOrder\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\SalesOrderItem;

class PrintSalesOrderItemLabelAction
{
    use AsAction;

    /**
     * Lưu lịch sử in và cộng dồn khối lượng đã in tem của dòng hàng.
     *
     * @param  array{weight_per_label: float|string, label_count: int, mfg_date: ?string, exp_date: string, supplier_name: ?string}  $data
     */
    public function handle(SalesOrderItem $item, array $data, ?string $printedBy): PrintLog
    {
        return DB::transaction(function () use ($item, $data, $printedBy) {
            $log = PrintLog::create([
                'order_item_id'    => $item->id,
                'weight_per_label' => $data['weight_per_label'],
                'label_count'      => $data['label_count'],
                'mfg_date'         => $data['mfg_date'] ?? null,
                'exp_date'         => $data['exp_date'],
                'supplier_name'    => $data['supplier_name'] ?? null,
                'printed_by'       => $printedBy,
            ]);

            $item->increment('printed_qty', round((float) $data['weight_per_label'] * (int) $data['label_count'], 3));

            return $log;
        });
    }
}
