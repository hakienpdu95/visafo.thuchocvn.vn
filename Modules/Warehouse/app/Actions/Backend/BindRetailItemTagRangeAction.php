<?php

namespace Modules\Warehouse\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Warehouse\Enums\RetailItemTagStatus;
use Modules\Warehouse\Models\Batch;

class BindRetailItemTagRangeAction
{
    use AsAction;

    public function handle(Batch $batch, int $fromSequence, int $toSequence, ?string $prefix = null): int
    {
        $expectedCount = $toSequence - $fromSequence + 1;
        $prefix        = $prefix !== null && $prefix !== '' ? strtoupper($prefix) : null;

        return DB::transaction(function () use ($batch, $fromSequence, $toSequence, $expectedCount, $prefix) {
            $baseQuery = fn () => DB::table('retail_item_tags')
                ->whereBetween('visual_sequence', [$fromSequence, $toSequence])
                ->where('status', RetailItemTagStatus::Provisioned->value)
                ->when($prefix, fn ($q) => $q->where('gs1_serial', 'like', $prefix . '%'));

            $availableCount = $baseQuery()->lockForUpdate()->count();

            if ($availableCount !== $expectedCount) {
                $prefixHint = $prefix ? " với prefix \"{$prefix}\"" : '';

                throw ValidationException::withMessages([
                    'to_sequence' => "Dải số {$fromSequence}–{$toSequence}{$prefixHint} không hợp lệ: cần {$expectedCount} tem đang ở trạng thái \"Đã in — chưa gắn kết\", nhưng chỉ tìm thấy {$availableCount}. Có thể một phần dải đã được gắn kết, prefix không khớp, hoặc tem không tồn tại.",
                ]);
            }

            $serialOffset = (int) DB::table('retail_item_tags')
                ->where('batch_id', $batch->id)
                ->lockForUpdate()
                ->max('serial_number');

            return $baseQuery()->update([
                'product_id'    => $batch->product_id,
                'batch_id'      => $batch->id,
                'status'        => RetailItemTagStatus::Bound->value,
                'serial_number' => DB::raw('visual_sequence - ' . (int) $fromSequence . ' + ' . ($serialOffset + 1)),
                'updated_at'    => now(),
            ]);
        });
    }
}
