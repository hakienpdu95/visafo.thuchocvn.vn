<?php

namespace Modules\SalesOrder\Actions\Backend;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\PrintLogAttribute;
use Modules\SalesOrder\Models\SalesOrderItem;

class PrintSalesOrderItemLabelAction
{
    use AsAction;

    /**
     * Định danh từng tem: in N tem → tạo đúng N bản ghi PrintLog, mỗi bản ghi có trace_code riêng
     * và bản sao thông tin bổ sung (EAV) riêng; các tem cùng lần in chung một print_session_id.
     * Cộng dồn khối lượng đã in tem (N × khối lượng/tem) vào dòng hàng trong cùng transaction.
     *
     * @param  array{weight_per_label: float|string, label_count: int, mfg_date: ?string, exp_date: string, supplier_name: ?string, batch_code?: ?string, label_template_id?: ?string, extra_attributes?: array<int, array{key: string, value: ?string}>}  $data
     * @return array{session_id: string, logs: Collection<int, PrintLog>}
     */
    public function handle(SalesOrderItem $item, array $data, ?string $printedBy): array
    {
        return DB::transaction(function () use ($item, $data, $printedBy) {
            $sessionId = Str::lower((string) Str::ulid());
            $count = (int) $data['label_count'];
            $logs = new Collection();

            for ($i = 0; $i < $count; $i++) {
                $log = PrintLog::create([
                    'print_session_id'  => $sessionId,
                    'order_item_id'     => $item->id,
                    'label_template_id' => ($data['label_template_id'] ?? null) ?: null,
                    'weight_per_label'  => $data['weight_per_label'],
                    'mfg_date'          => $data['mfg_date'] ?? null,
                    'exp_date'          => $data['exp_date'],
                    'supplier_name'     => $data['supplier_name'] ?? null,
                    'batch_code'        => $data['batch_code'] ?? null,
                    'printed_by'        => $printedBy,
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

            $item->increment('printed_qty', round((float) $data['weight_per_label'] * $count, 3));

            return ['session_id' => $sessionId, 'logs' => $logs];
        });
    }
}
