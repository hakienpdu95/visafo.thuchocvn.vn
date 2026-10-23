<?php

namespace Modules\SalesOrder\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Models\SalesOrderItem;
use Modules\SalesOrder\Support\SalesOrderItemRemover;

class UpdateSalesOrderAction
{
    use AsAction;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array{product_id: string, line_no: ?int, product_name_raw: ?string, unit_raw: ?string, requested_qty: float}>  $items
     */
    public function handle(SalesOrder $order, array $attributes, array $items, ?string $userId = null): SalesOrder
    {
        return DB::transaction(function () use ($order, $attributes, $items, $userId) {
            $order = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);

            if ($reason = $order->lockReason()) {
                throw ValidationException::withMessages([
                    'order' => $reason . ' Không thể sửa đơn.',
                ]);
            }

            $existing = $order->items()->get()->keyBy('product_id');
            $submitted = collect($items)->keyBy('product_id');
            $qcIds = SalesOrderItemRemover::qcItemIds($existing->pluck('id')->all());
            $errors = [];

            foreach ($existing->filter(fn (SalesOrderItem $i) => in_array($i->id, $qcIds, true)) as $productId => $item) {
                $name = $item->product_name_raw ?? $productId;
                $next = $submitted->get($productId);

                if ($next === null) {
                    $errors[] = "\"$name\" đã có biên bản QC trước xuất — không bỏ được dòng này.";
                } elseif (round((float) $next['requested_qty'], 3) != round((float) $item->requested_qty, 3)) {
                    $errors[] = "\"$name\" đã có biên bản QC trước xuất — không đổi được số lượng.";
                }
            }

            if ($errors) {
                throw ValidationException::withMessages(['items' => implode(' ', $errors)]);
            }

            $order->update($attributes);

            foreach ($items as $item) {
                $current = $existing->get($item['product_id']);

                if ($current) {
                    $current->update([
                        'line_no'       => $item['line_no'],
                        'requested_qty' => $item['requested_qty'],
                    ]);

                    continue;
                }

                SalesOrderItem::create([
                    ...$item,
                    'order_id'   => $order->id,
                    'actual_qty' => null,
                ]);
            }

            SalesOrderItemRemover::remove(
                $existing->diffKeys($submitted)->values(),
                'Dòng hàng đã được gỡ khỏi đơn ' . $order->misa_ref_id . ' khi sửa đơn.',
                $userId,
            );

            return $order;
        });
    }
}
