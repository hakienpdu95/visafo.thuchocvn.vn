<?php

namespace Modules\TraceLog\Actions\Backend;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesOrder\Enums\PrintLogStatus;
use Modules\SalesOrder\Models\PrintLog;

class RestoreTraceLogsAction
{
    use AsAction;

    /** @return list<array{order_item_id: string, product_name: ?string, order_ref: ?string, restore_codes: list<string>, active_codes: list<string>, item_deleted: bool}> */
    public function preview(array $ids): array
    {
        return $this->summarize($this->revoked($ids, false));
    }

    /** @return array{restored: int, replaced: int} */
    public function handle(array $ids, string $reason, bool $replaceActive, ?string $userId): array
    {
        return DB::transaction(function () use ($ids, $reason, $replaceActive, $userId) {
            $logs = $this->revoked($ids, true);

            if ($logs->isEmpty()) {
                throw ValidationException::withMessages(['ids' => 'Không có mã nào ở trạng thái "Đã hủy" để khôi phục.']);
            }

            $deleted = $logs->filter(fn (PrintLog $log) => $log->orderItem === null || $log->orderItem->trashed());
            if ($deleted->isNotEmpty()) {
                throw ValidationException::withMessages(['ids' => 'Không thể khôi phục mã thuộc dòng hàng đã bị xóa khỏi đơn: '
                    . $deleted->map(fn (PrintLog $log) => strtoupper($log->trace_code))->implode(', ') . '.']);
            }

            $now = now();
            $replaced = 0;

            if ($replaceActive) {
                $active = PrintLog::query()
                    ->whereIn('order_item_id', $logs->pluck('order_item_id')->unique())
                    ->where('status', PrintLogStatus::Active->value)
                    ->whereNotIn('id', $logs->pluck('id'))
                    ->lockForUpdate()
                    ->get();

                foreach ($active as $log) {
                    $log->update([
                        'status'            => PrintLogStatus::Revoked,
                        'status_reason'     => mb_substr('Đã thay bằng mã tem cũ được khôi phục. Lý do: ' . $reason, 0, 255),
                        'status_changed_by' => $userId,
                        'status_changed_at' => $now,
                    ]);
                }
                $replaced = $active->count();
            }

            foreach ($logs as $log) {
                $log->update([
                    'status'            => PrintLogStatus::Active,
                    'status_reason'     => mb_substr('Khôi phục mã: ' . $reason, 0, 255),
                    'status_changed_by' => $userId,
                    'status_changed_at' => $now,
                ]);
            }

            return ['restored' => $logs->count(), 'replaced' => $replaced];
        });
    }

    /** @return Collection<int, PrintLog> */
    private function revoked(array $ids, bool $lock): Collection
    {
        return PrintLog::query()
            ->whereIn('id', array_values(array_unique($ids)))
            ->where('status', PrintLogStatus::Revoked->value)
            ->with(['orderItem.product:id,name', 'orderItem.salesOrder:id,misa_ref_id'])
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->orderBy('created_at')->orderBy('id')
            ->get();
    }

    private function summarize(Collection $logs): array
    {
        $active = PrintLog::query()
            ->whereIn('order_item_id', $logs->pluck('order_item_id')->unique())
            ->where('status', PrintLogStatus::Active->value)
            ->orderBy('created_at')->orderBy('id')
            ->get(['id', 'order_item_id', 'trace_code'])
            ->groupBy('order_item_id');

        return $logs->groupBy('order_item_id')->map(function (Collection $group, string $itemId) use ($active) {
            $item = $group->first()->orderItem;

            return [
                'order_item_id' => $itemId,
                'product_name'  => $item?->product?->name ?? $item?->product_name_raw,
                'order_ref'     => $item?->salesOrder?->misa_ref_id,
                'restore_codes' => $group->pluck('trace_code')->map(fn ($c) => strtoupper($c))->values()->all(),
                'active_codes'  => $active->get($itemId, collect())->pluck('trace_code')->map(fn ($c) => strtoupper($c))->values()->all(),
                'item_deleted'  => $item === null || $item->trashed(),
            ];
        })->values()->all();
    }
}
