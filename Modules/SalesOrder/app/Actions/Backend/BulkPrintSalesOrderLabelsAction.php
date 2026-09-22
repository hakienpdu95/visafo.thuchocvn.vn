<?php

namespace Modules\SalesOrder\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\PrintLogAttribute;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Models\SalesOrderItem;
use Modules\SalesOrder\Support\BatchAttributeResolver;

class BulkPrintSalesOrderLabelsAction
{
    use AsAction;

    /**
     * In tem cho mọi dòng còn thiếu (yêu cầu > đã in), áp dụng CHUNG một cấu hình (mẫu tem, NSX, HSD, mã lô,
     * nguồn cung) do người dùng khai báo một lần cho cả đơn. Khối lượng mỗi tem vẫn tự động = phần còn lại
     * của từng dòng (một PrintLog/tem cho mỗi dòng).
     *
     * `reprint_all`: người dùng đã chủ động xác nhận in lại dù dòng đó đã in đủ/thừa (tem rách/hỏng cần in bù)
     * — khi đó bỏ qua điều kiện "còn thiếu" và dùng số lượng yêu cầu ban đầu của dòng làm khối lượng/tem.
     *
     * @param  array{reprint_all?: bool, label_template_id?: ?string, mfg_date?: ?string, exp_date: string, supplier_name?: ?string, batch_code?: ?string}  $data
     * @return array{session_id: string, logs: PrintLog[], total: int, printed: int, items: array<int, array{id: string, printed_qty: string, printed_qty_raw: float}>}
     */
    public function __construct(private readonly BatchAttributeResolver $resolver) {}

    public function handle(SalesOrder $order, array $data, ?string $printedBy): array
    {
        $resolver = $this->resolver;
        $reprintAll = (bool) ($data['reprint_all'] ?? false);

        return DB::transaction(function () use ($order, $data, $reprintAll, $printedBy, $resolver) {
            // Khóa dòng hàng để hai lần bấm đồng thời không in trùng phần còn lại.
            $items = SalesOrderItem::query()
                ->where('order_id', $order->id)
                ->with('product')
                ->orderBy('line_no')
                ->lockForUpdate()
                ->get();

            $sessionId = Str::lower((string) Str::ulid());
            $logs = [];
            $updated = [];
            $total = 0;

            foreach ($items as $item) {
                $remaining = round((float) $item->requested_qty - (float) $item->printed_qty, 3);
                $weight = $remaining;

                if ($remaining <= 0) {
                    if (!$reprintAll) {
                        continue;
                    }

                    // In lại toàn bộ: dùng đúng khối lượng yêu cầu ban đầu của dòng làm khối lượng/tem.
                    $weight = round((float) $item->requested_qty, 3);
                    if ($weight <= 0) {
                        continue;
                    }
                }

                $total++;

                $logs[] = PrintLog::create([
                    'print_session_id'  => $sessionId,
                    'order_item_id'     => $item->id,
                    // Mẫu chọn chung ưu tiên; để trống thì mẫu gán riêng cho sản phẩm/mặc định hệ thống
                    // sẽ được LabelViewResolver tự suy ra lúc render (xem forLog/forProduct).
                    'label_template_id' => $data['label_template_id'] ?? null,
                    'weight_per_label'  => $weight,
                    'mfg_date'          => $data['mfg_date'] ?? null,
                    'exp_date'          => $data['exp_date'],
                    'supplier_name'     => $data['supplier_name'] ?? null,
                    'batch_code'        => $data['batch_code'] ?? null,
                    'printed_by'        => $printedBy,
                ]);

                foreach ($resolver->forItem($item)['attributes'] as $attr) {
                    PrintLogAttribute::create([
                        'print_log_id'    => end($logs)->id,
                        'attribute_key'   => $attr['key'],
                        'attribute_value' => $attr['value'],
                    ]);
                }

                $item->increment('printed_qty', $weight);
                $printedQty = (float) $item->fresh()->printed_qty;

                $updated[] = [
                    'id'              => $item->id,
                    'printed_qty'     => number_format($printedQty, 3),
                    'printed_qty_raw' => $printedQty,
                ];
            }

            return [
                'session_id' => $sessionId,
                'logs'    => $logs,
                'total'   => $total,
                'printed' => count($logs),
                'items'   => $updated,
            ];
        });
    }
}
