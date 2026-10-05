<?php

namespace Modules\TraceLog\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\LabelPrintEvent;
use Modules\SalesOrder\Models\PrintLog;
use Modules\SalesOrder\Models\PrintLogAttribute;

/**
 * "Hủy mã & Cấp lại": tem mất / hỏng → hủy mã cũ (status = revoked, người quét mã cũ thấy cảnh báo)
 * rồi tạo tem mới cùng toàn bộ dữ liệu đã in nhưng mã TXNG mới. Đây là cách DUY NHẤT để có mã mới
 * cho một dòng hàng đã có tem đang lưu hành — "In tem" thường luôn dùng lại mã cũ.
 */
class ReissueTraceLogAction
{
    use AsAction;

    public function handle(PrintLog $printLog, string $reason, ?string $userId): PrintLog
    {
        return DB::transaction(function () use ($printLog, $reason, $userId) {
            /** @var PrintLog $old */
            $old = PrintLog::query()->with('attributes')->lockForUpdate()->findOrFail($printLog->id);

            // Tem đã thu hồi (sản phẩm có vấn đề) không được dán tem mới; tem đã hủy thì đã có mã thay thế.
            if (! in_array($old->status, [PrintLogStatus::Active, PrintLogStatus::Error], true)) {
                throw ValidationException::withMessages([
                    'reason' => 'Chỉ cấp lại được tem đang lưu hành hoặc tem lỗi (tem này: ' . $old->status->label() . ').',
                ]);
            }

            $new = PrintLog::create([
                'print_session_id'  => Str::lower((string) Str::ulid()),
                'order_item_id'     => $old->order_item_id,
                'label_template_id' => $old->label_template_id,
                'weight_per_label'  => $old->weight_per_label,
                'mfg_date'          => $old->mfg_date,
                'exp_date'          => $old->exp_date,
                'supplier_name'     => $old->supplier_name,
                'vendor_id'         => $old->vendor_id,
                'product_batch_id'  => $old->product_batch_id,
                'batch_code'        => $old->batch_code,
                'printed_by'        => $userId,
                'status'            => PrintLogStatus::Active,
            ]); // trace_code mới sinh trong PrintLog::creating

            foreach ($old->attributes as $attr) {
                PrintLogAttribute::create([
                    'print_log_id'    => $new->id,
                    'attribute_key'   => $attr->attribute_key,
                    'attribute_value' => $attr->attribute_value,
                ]);
            }

            LabelPrintEvent::record([$new], $new->print_session_id, $userId, false);

            // Lý do hiển thị cho người quét mã cũ → ghi rõ đã có mã thay thế
            $old->update([
                'status'            => PrintLogStatus::Revoked,
                'status_reason'     => Str::limit('Đã cấp lại mã ' . strtoupper($new->trace_code) . '. ' . $reason, 255),
                'status_changed_by' => $userId,
                'status_changed_at' => now(),
            ]);

            return $new;
        });
    }
}
