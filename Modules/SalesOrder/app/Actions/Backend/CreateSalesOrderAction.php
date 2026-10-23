<?php

namespace Modules\SalesOrder\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\SalesOrder\Models\SalesOrder;
use Modules\SalesOrder\Models\SalesOrderItem;

class CreateSalesOrderAction
{
    use AsAction;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array{product_id: string, line_no: ?int, product_name_raw: ?string, unit_raw: ?string, requested_qty: float}>  $items
     */
    public function handle(array $attributes, array $items): SalesOrder
    {
        return DB::transaction(function () use ($attributes, $items) {
            $order = SalesOrder::create([
                ...$attributes,
                'status' => SalesOrder::STATUS_PENDING,
            ]);

            foreach ($items as $item) {
                SalesOrderItem::create([
                    ...$item,
                    'order_id'   => $order->id,
                    'actual_qty' => null,
                ]);
            }

            return $order;
        });
    }
}
