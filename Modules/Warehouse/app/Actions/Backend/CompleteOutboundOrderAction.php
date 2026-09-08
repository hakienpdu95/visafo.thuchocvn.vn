<?php

namespace Modules\Warehouse\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Enums\OutboundOrderStatus;
use Modules\Warehouse\Enums\RetailItemTagStatus;
use Modules\Warehouse\Models\Batch;
use Modules\Warehouse\Models\OutboundOrder;
use Modules\Warehouse\Models\RetailItemTag;

class CompleteOutboundOrderAction
{
    use AsAction;

    public function handle(OutboundOrder $order): OutboundOrder
    {
        if ($order->pickedBatches->isEmpty()) {
            throw ValidationException::withMessages([
                'order' => 'Đơn xuất buôn chưa có lô hàng nào được chọn.',
            ]);
        }

        DB::transaction(function () use ($order) {
            foreach ($order->pickedBatches as $line) {
                $batch = Batch::lockForUpdate()->findOrFail($line->batch_id);

                if ($line->quantity > $batch->current_qty) {
                    throw ValidationException::withMessages([
                        'order' => "Lô \"{$batch->internal_batch_code}\" không còn đủ {$line->quantity} đơn vị (hiện còn {$batch->current_qty}).",
                    ]);
                }

                $batch->decrement('current_qty', $line->quantity);

                // Tem chưa lưu hành (bound) khi xuất kho vẫn tiếp tục bị khóa (transferred) —
                // đại lý phải chờ "Kích hoạt lưu hành đơn hàng" mới quét ra được thông tin.
                // outbound_order_id được ghi lại ngay tại đây — đây là điểm neo duy nhất cho
                // phép truy vết ngược "đại lý nào đang giữ serial nào" (chống bán phá giá).
                $boundIds = RetailItemTag::where('batch_id', $batch->id)
                    ->where('status', RetailItemTagStatus::Bound->value)
                    ->orderByRaw('visual_sequence IS NULL, visual_sequence ASC')
                    ->limit($line->quantity)
                    ->pluck('id');
                RetailItemTag::whereIn('id', $boundIds)->update([
                    'status'             => RetailItemTagStatus::Transferred->value,
                    'outbound_order_id'  => $order->id,
                ]);

                // Tem đã lưu hành từ trước (in_stock) thì giữ nguyên quyền quét, chỉ đổi vị trí kho.
                $remaining = $line->quantity - $boundIds->count();
                if ($remaining > 0) {
                    $activeIds = RetailItemTag::where('batch_id', $batch->id)
                        ->where('status', RetailItemTagStatus::InStock->value)
                        ->orderByRaw('visual_sequence IS NULL, visual_sequence ASC')
                        ->limit($remaining)
                        ->pluck('id');
                    RetailItemTag::whereIn('id', $activeIds)->update([
                        'status'            => RetailItemTagStatus::TransferredActive->value,
                        'outbound_order_id' => $order->id,
                    ]);
                }
            }

            $order->update([
                'status'       => OutboundOrderStatus::Completed->value,
                'completed_at' => now(),
            ]);
        });

        return $order->fresh();
    }
}
