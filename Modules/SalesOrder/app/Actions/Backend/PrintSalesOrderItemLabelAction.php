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
     * Mặc định: tem đang lưu hành trùng khối lượng/tem được dùng lại, chỉ sinh mã mới cho phần không trùng; không bao giờ tự hủy tem cũ.
     * Không có phần nào cần sinh mới → in lại đúng các mã cũ trùng khối lượng (chọn nguồn / lô khác thì cập nhật nguồn của chính các tem đó).
     * Hủy tem chỉ làm ở danh sách tem truy xuất.
     *
     * @return array{session_id: string, logs: Collection<int, PrintLog>, reused: bool, source_changed: bool, appended: bool}
     */
    public function handle(SalesOrderItem $item, array $data, ?string $printedBy): array
    {
        return DB::transaction(function () use ($item, $data, $printedBy) {
            // Khóa dòng hàng: bấm "In" 2 lần liên tiếp không sinh 2 bộ mã
            SalesOrderItem::query()->whereKey($item->id)->lockForUpdate()->first();

            $sessionId = Str::lower((string) Str::ulid());

            $existing = PrintLog::query()->activeForItem($item->id)->get();
            $groups = collect($data['label_groups'])->map(fn ($g) => ['weight' => (float) $g['weight_per_label'], 'qty' => (int) $g['label_count']])->all();
            $appended = false;
            if ($existing->isNotEmpty()) {
                [$reuse, $groups] = PrintLog::matchExisting($existing, $groups);
                if ($groups === []) {
                    $sourceChanged = PrintLog::sourceDiffers($reuse, $data);
                    if ($sourceChanged) {
                        PrintLog::updateSource($reuse, $data);
                    }
                    $reuse->each(fn (PrintLog $log) => $log->markReprinted($sessionId));
                    LabelPrintEvent::record($reuse, $sessionId, $printedBy, true);

                    return ['session_id' => $sessionId, 'logs' => $reuse, 'reused' => true, 'source_changed' => $sourceChanged, 'appended' => false];
                }
                $appended = true;
            }

            $logs = new Collection();

            foreach ($groups as $group) {
                $weight = round($group['weight'], 3);

                for ($i = 0; $i < $group['qty']; $i++) {
                    $log = PrintLog::create([
                        'print_session_id'  => $sessionId,
                        'order_item_id'     => $item->id,
                        'label_template_id' => ($data['label_template_id'] ?? null) ?: null,
                        'weight_per_label'  => $weight,
                        'mfg_date'          => $data['mfg_date'] ?? null,
                        'exp_date'          => $data['exp_date'],
                        'supplier_name'     => $data['supplier_name'] ?? null,
                        'vendor_id'         => $data['vendor_id'] ?? null,
                        'partner_product_id' => $data['partner_product_id'] ?? null,
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

            return ['session_id' => $sessionId, 'logs' => $logs, 'reused' => false, 'source_changed' => false, 'appended' => $appended];
        });
    }
}
