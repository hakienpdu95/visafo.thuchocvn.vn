<?php

namespace Modules\Warehouse\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Enums\RetailItemTagStatus;
use Modules\Warehouse\Models\Batch;

class UnbindRetailItemTagRangeAction
{
    use AsAction;

    private const UNBINDABLE_STATUSES = [
        RetailItemTagStatus::Bound->value,
        RetailItemTagStatus::InStock->value,
    ];

    /** Gỡ hàng loạt một dải tem đã gán nhầm cho lô — chỉ áp dụng khi chưa bán/chưa xuất buôn. */
    public function handle(Batch $batch, int $fromSequence, int $toSequence): int
    {
        return DB::transaction(function () use ($batch, $fromSequence, $toSequence) {
            $query = DB::table('retail_item_tags')
                ->where('batch_id', $batch->id)
                ->whereBetween('visual_sequence', [$fromSequence, $toSequence]);

            $total          = (clone $query)->lockForUpdate()->count();
            $unbindableOnly = (clone $query)->whereIn('status', self::UNBINDABLE_STATUSES)->count();

            if ($total === 0) {
                throw ValidationException::withMessages([
                    'range' => "Không tìm thấy tem nào của lô này trong dải {$fromSequence}–{$toSequence}.",
                ]);
            }

            if ($unbindableOnly !== $total) {
                throw ValidationException::withMessages([
                    'range' => "Không thể gỡ dải {$fromSequence}–{$toSequence}: có tem đã bán/đã xuất buôn/hư hỏng/thu hồi trong dải này, chỉ gỡ được tem đang \"Chờ lưu hành\" hoặc \"Còn trên kệ\".",
                ]);
            }

            return $query->update([
                'product_id'    => null,
                'batch_id'      => null,
                'serial_number' => null,
                'status'        => RetailItemTagStatus::Provisioned->value,
                'updated_at'    => now(),
            ]);
        });
    }
}
