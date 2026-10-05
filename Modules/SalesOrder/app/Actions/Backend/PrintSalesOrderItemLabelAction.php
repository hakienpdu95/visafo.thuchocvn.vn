<?php

namespace Modules\SalesOrder\Actions\Backend;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\LabelPrintEvent;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\PrintLogAttribute;
use Modules\SalesOrder\Models\SalesOrderItem;

class PrintSalesOrderItemLabelAction
{
    use AsAction;

    /**
     * Check & Reuse: dòng hàng đã có tem đang lưu hành (cùng lô nhập nếu có) → in lại đúng các mã đó,
     * KHÔNG tạo bản ghi / mã TXNG mới (dữ liệu nhập trên form bị bỏ qua vì tem đã in là bất biến).
     * Muốn đổi khối lượng/NSX/HSD hoặc thay tem mất → "Hủy mã & Cấp lại" ở Nhật ký TXNG.
     *
     * @return array{session_id: string, logs: Collection<int, PrintLog>, reused: bool}
     */
    public function handle(SalesOrderItem $item, array $data, ?string $printedBy): array
    {
        return DB::transaction(function () use ($item, $data, $printedBy) {
            // Khóa dòng hàng: bấm "In" 2 lần liên tiếp không sinh 2 bộ mã
            SalesOrderItem::query()->whereKey($item->id)->lockForUpdate()->first();

            $sessionId = Str::lower((string) Str::ulid());

            $vendorScope = ($data['vendor_selected'] ?? false)
                ? $data['vendor_id']
                : PrintLog::query()->activeForItem($item->id, $data['product_batch_id'] ?? null)
                    ->reorder()->latest('last_printed_at')->latest('id')->value('vendor_id');

            $existing = PrintLog::query()->activeForItem($item->id, $data['product_batch_id'] ?? null, $vendorScope)->get();
            if ($existing->isNotEmpty()) {
                $existing->each(fn (PrintLog $log) => $log->markReprinted($sessionId, $data['vendor_id'] ?? null, $data['supplier_name'] ?? null));
                LabelPrintEvent::record($existing, $sessionId, $printedBy, true);

                return ['session_id' => $sessionId, 'logs' => $existing, 'reused' => true];
            }

            $logs = new Collection();

            foreach ($data['label_groups'] as $group) {
                $weight = round((float) $group['weight_per_label'], 3);

                for ($i = 0; $i < (int) $group['label_count']; $i++) {
                    $log = PrintLog::create([
                        'print_session_id'  => $sessionId,
                        'order_item_id'     => $item->id,
                        'label_template_id' => ($data['label_template_id'] ?? null) ?: null,
                        'weight_per_label'  => $weight,
                        'mfg_date'          => $data['mfg_date'] ?? null,
                        'exp_date'          => $data['exp_date'],
                        'supplier_name'     => $data['supplier_name'] ?? null,
                        'vendor_id'         => $data['vendor_id'] ?? null,
                        'product_batch_id'  => $data['product_batch_id'] ?? null,
                        'batch_code'        => $data['batch_code'] ?? null,
                        'printed_by'        => $printedBy,
                        'status'            => PrintLogStatus::Active,
                    ]); // trace_code độc nhất được sinh trong PrintLog::creating

                    foreach ($data['extra_attributes'] ?? [] as $attr) {
                        PrintLogAttribute::create([
                            'print_log_id'    => $log->id,
                            'attribute_key'   => $attr['key'],
                            'attribute_value' => $attr['value'] ?? null,
                        ]);
                    }

                    $logs->push($log);
                }
            }

            LabelPrintEvent::record($logs, $sessionId, $printedBy, false);

            return ['session_id' => $sessionId, 'logs' => $logs, 'reused' => false];
        });
    }
}
