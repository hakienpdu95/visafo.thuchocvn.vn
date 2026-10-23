<?php

namespace Modules\SalesOrder\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Support\SalesOrderItemRemover;

class DeleteSalesOrderAction
{
    use AsAction;

    public function handle(SalesOrder $order, ?string $userId = null): void
    {
        DB::transaction(function () use ($order, $userId) {
            $order = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);

            if ($reason = $order->deleteBlockReason()) {
                throw ValidationException::withMessages([
                    'order' => $reason . ' Không thể xóa đơn.',
                ]);
            }

            $keep = $order->hasPrintHistory();

            SalesOrderItemRemover::remove(
                $order->items()->withTrashed()->get(),
                'Đơn ' . $order->misa_ref_id . ' đã bị xóa.',
                $userId,
            );

            $keep ? $order->delete() : $order->forceDelete();
        });
    }
}
