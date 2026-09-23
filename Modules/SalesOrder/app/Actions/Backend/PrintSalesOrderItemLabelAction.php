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

    public function handle(SalesOrderItem $item, array $data, ?string $printedBy): array
    {
        return DB::transaction(function () use ($item, $data, $printedBy) {
            $sessionId = Str::lower((string) Str::ulid());
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

            return ['session_id' => $sessionId, 'logs' => $logs];
        });
    }
}
